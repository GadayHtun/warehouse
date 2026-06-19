<?php

namespace App\Checks;

use App\Contracts\AgentCheck;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Detects stock transfers where the out quantity at source does not
 * match the in quantity at destination within a 24-hour grace period.
 * Severity: warning. Frequency: daily.
 */
class UnbalancedTransfersCheck implements AgentCheck
{
    private const GRACE_PERIOD_HOURS = 24;

    public function run(): array
    {
        // Find transfer-out movements
        $transferOuts = StockMovement::query()
            ->where('direction', 'out')
            ->where('reference_note', 'like', '%transfer%')
            ->where('created_at', '>=', now()->subDays(3))
            ->with(['product', 'location'])
            ->get();

        $findings = [];

        foreach ($transferOuts as $out) {
            // Look for a matching transfer-in within the grace period
            $matchingIn = StockMovement::query()
                ->where('direction', 'in')
                ->where('product_id', $out->product_id)
                ->where('reference_note', 'like', '%transfer%')
                ->whereBetween('created_at', [
                    $out->created_at,
                    $out->created_at->copy()->addHours(self::GRACE_PERIOD_HOURS),
                ])
                ->first();

            if ($matchingIn) {
                if (abs($out->quantity - $matchingIn->quantity) > 0.001) {
                    $findings[] = [
                        'check_type' => 'unbalanced_transfers',
                        'severity' => 'warning',
                        'product_id' => $out->product_id,
                        'location_id' => null,
                        'title' => sprintf(
                            'Unbalanced transfer: %s (out: %.3f, in: %.3f)',
                            $out->product->name ?? 'Product #' . $out->product_id,
                            $out->quantity,
                            $matchingIn->quantity,
                        ),
                        'description' => sprintf(
                            'Transfer quantity mismatch for "%s" (SKU: %s): '
                            . '%.3f units sent from %s → %.3f units received at %s. '
                            . 'Difference: %.3f units. Investigate whether goods were lost in transit '
                            . 'or if one of the transfer records is incorrect.',
                            $out->product->name ?? 'Unknown',
                            $out->product->sku ?? 'N/A',
                            $out->quantity,
                            $out->location->name ?? 'Unknown',
                            $matchingIn->quantity,
                            $matchingIn->location->name ?? 'Unknown',
                            abs($out->quantity - $matchingIn->quantity),
                        ),
                        'dedup_hash' => md5("unbalanced_transfer:{$out->id}:{$matchingIn->id}"),
                    ];
                }
            }
        }

        return $findings;
    }
}
