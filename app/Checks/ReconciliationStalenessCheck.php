<?php

namespace App\Checks;

use App\Contracts\AgentCheck;
use App\Models\CurrentStock;
use App\Models\ReconciliationCountLine;

/**
 * Detects product-locations where the last reconciliation was more than
 * 90 days ago. Ensures all locations are reconciled regularly.
 * Severity: info. Frequency: weekly.
 */
class ReconciliationStalenessCheck implements AgentCheck
{
    private const STALENESS_DAYS = 90;

    public function run(): array
    {
        $cutoff = now()->subDays(self::STALENESS_DAYS);

        // Product-locations that have stock but haven't been reconciled recently
        $lastReconciled = ReconciliationCountLine::query()
            ->selectRaw('product_id, session_id, MAX(sessions.location_id) as location_id')
            ->join('reconciliation_sessions as sessions', 'reconciliation_count_lines.session_id', '=', 'sessions.id')
            ->groupBy('product_id', 'session_id')
            ->get()
            ->groupBy('product_id');

        $stockItems = CurrentStock::query()
            ->where('quantity_on_hand', '>', 0)
            ->with(['product', 'location'])
            ->get();

        $findings = [];

        foreach ($stockItems as $stock) {
            $lastSessions = $lastReconciled->get($stock->product_id);
            $recentlyReconciled = false;

            if ($lastSessions) {
                foreach ($lastSessions as $session) {
                    $sessionDate = \App\Models\ReconciliationSession::find($session->session_id)?->submitted_at;
                    if ($sessionDate && $sessionDate >= $cutoff) {
                        $recentlyReconciled = true;
                        break;
                    }
                }
            }

            if (!$recentlyReconciled) {
                $findings[] = [
                    'check_type' => 'reconciliation_staleness',
                    'severity' => 'info',
                    'product_id' => $stock->product_id,
                    'location_id' => $stock->location_id,
                    'title' => sprintf(
                        'Reconciliation overdue: %s at %s (%d+ days)',
                        $stock->product->name ?? 'Product #' . $stock->product_id,
                        $stock->location->code ?? 'Location #' . $stock->location_id,
                        self::STALENESS_DAYS,
                    ),
                    'description' => sprintf(
                        'Product "%s" (SKU: %s) at %s has %.3f units on hand but has not been '
                        . 'reconciled in %d+ days. Regular reconciliation is required for inventory accuracy. '
                        . 'Schedule a reconciliation session for this product-location.',
                        $stock->product->name ?? 'Unknown',
                        $stock->product->sku ?? 'N/A',
                        $stock->location->name ?? 'Unknown',
                        $stock->quantity_on_hand,
                        self::STALENESS_DAYS,
                    ),
                    'dedup_hash' => md5("reconciliation_staleness:{$stock->product_id}:{$stock->location_id}"),
                ];
            }
        }

        return $findings;
    }
}
