<?php

namespace App\Services;

use App\Contracts\AgentCheck;
use App\Models\AgentCheckRun;
use App\Models\AgentFinding;

class AgentOrchestrator
{
    /**
     * Run all agent checks and persist findings.
     * Deduplicates: skips findings whose dedup_hash matches an existing OPEN finding.
     */
    public function runAll(): array
    {
        $checks = $this->getAllChecks();
        $results = [];

        foreach ($checks as $check) {
            $results[] = $this->runCheck($check);
        }

        return $results;
    }

    /**
     * Run a single agent check by class name.
     */
    public function runCheckByName(string $checkClass): array
    {
        if (!class_exists($checkClass)) {
            throw new \InvalidArgumentException("Agent check class not found: {$checkClass}");
        }

        $check = app($checkClass);

        if (!($check instanceof AgentCheck)) {
            throw new \InvalidArgumentException("{$checkClass} does not implement AgentCheck");
        }

        return $this->runCheck($check);
    }

    /**
     * Execute a single check, persist findings, and record the run.
     */
    private function runCheck(AgentCheck $check): array
    {
        $checkType = class_basename($check);

        $run = AgentCheckRun::create([
            'check_type' => $checkType,
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $findings = $check->run();

            $inserted = 0;
            foreach ($findings as $finding) {
                // Deduplication: skip if an open finding with the same hash exists
                $exists = AgentFinding::query()
                    ->where('dedup_hash', $finding['dedup_hash'])
                    ->where('status', 'open')
                    ->exists();

                if (!$exists) {
                    AgentFinding::create($finding);
                    $inserted++;
                }
            }

            $run->update([
                'findings_count' => $inserted,
                'completed_at' => now(),
                'status' => 'completed',
            ]);

            return [
                'check_type' => $checkType,
                'status' => 'completed',
                'findings_count' => $inserted,
                'total_returned' => count($findings),
            ];
        } catch (\Throwable $e) {
            $run->update([
                'completed_at' => now(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            report($e);

            return [
                'check_type' => $checkType,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all registered agent check instances.
     * Maps from the feature spec section 4.6.1.
     */
    private function getAllChecks(): array
    {
        return [
            app(\App\Checks\NegativeStockCheck::class),
            app(\App\Checks\DormantStockCheck::class),
            app(\App\Checks\RapidDepletionCheck::class),
            app(\App\Checks\VarianceDriftCheck::class),
            app(\App\Checks\ReconciliationStalenessCheck::class),
            app(\App\Checks\DuplicateMovementsCheck::class),
            app(\App\Checks\UnbalancedTransfersCheck::class),
            app(\App\Checks\PriceAnomalyCheck::class),
        ];
    }
}
