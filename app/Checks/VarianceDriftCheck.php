<?php

namespace App\Checks;

use App\Contracts\AgentCheck;
use App\Models\ReconciliationCountLine;
use Illuminate\Support\Facades\DB;

/**
 * Detects products where the absolute variance between physical count and
 * system quantity is growing across consecutive reconciliation sessions.
 * Severity: warning. Frequency: after each reconciliation close.
 */
class VarianceDriftCheck implements AgentCheck
{
    public function run(): array
    {
        // Find products that appear in 2+ reconciliation sessions and
        // have increasing absolute variance
        $drifts = ReconciliationCountLine::query()
            ->select(
                'product_id',
                'session_id',
                DB::raw('ABS(variance) as abs_variance'),
                'variance_percentage',
            )
            ->whereNotNull('variance')
            ->whereHas('session', fn ($q) => $q->where('status', 'closed'))
            ->with(['product', 'session.location'])
            ->orderBy('product_id')
            ->orderBy('session_id')
            ->get()
            ->groupBy('product_id');

        $findings = [];

        foreach ($drifts as $productId => $lines) {
            if ($lines->count() < 2) {
                continue;
            }

            // Check if variance is growing
            $prevAbs = null;
            foreach ($lines as $line) {
                if ($prevAbs !== null && $line->abs_variance > $prevAbs * 1.1) {
                    $firstLine = $lines->first();
                    $product = $firstLine->product;

                    $findings[] = [
                        'check_type' => 'variance_drift',
                        'severity' => 'warning',
                        'product_id' => $productId,
                        'location_id' => $line->session->location_id ?? null,
                        'title' => sprintf(
                            'Variance drift detected: %s',
                            $product->name ?? 'Product #' . $productId,
                        ),
                        'description' => sprintf(
                            'Product "%s" (SKU: %s) shows growing variance across %d reconciliation sessions. '
                            . 'Latest absolute variance: %.3f units (%.1f%%). '
                            . 'This may indicate a systemic issue with how this product is being counted, '
                            . 'received, or dispensed. Investigate the receiving and stock-out processes.',
                            $product->name ?? 'Unknown',
                            $product->sku ?? 'N/A',
                            $lines->count(),
                            $line->abs_variance,
                            $line->variance_percentage ?? 0,
                        ),
                        'dedup_hash' => md5("variance_drift:{$productId}:{$line->session->location_id}"),
                    ];
                    break; // One finding per product
                }
                $prevAbs = $line->abs_variance;
            }
        }

        return $findings;
    }
}
