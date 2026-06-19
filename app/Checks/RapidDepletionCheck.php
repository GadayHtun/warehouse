<?php

namespace App\Checks;

use App\Contracts\AgentCheck;
use App\Models\StockMovement;
use App\Models\CurrentStock;
use Illuminate\Support\Facades\DB;

/**
 * Detects products where the stock-out rate over the last 7 days
 * exceeds the 30-day average by 2× or more.
 * Severity: warning. Frequency: daily.
 */
class RapidDepletionCheck implements AgentCheck
{
    public function run(): array
    {
        $sevenDaysAgo = now()->subDays(7);
        $thirtyDaysAgo = now()->subDays(30);

        // Sum stock-out quantities for 7-day and 30-day windows by product-location
        $recentOuts = StockMovement::query()
            ->select(
                'product_id',
                'location_id',
                DB::raw('SUM(CASE WHEN created_at >= ? THEN quantity ELSE 0 END) as qty_7d', [$sevenDaysAgo]),
                DB::raw('SUM(quantity) as qty_30d'),
            )
            ->where('direction', 'out')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy('product_id', 'location_id')
            ->get();

        $findings = [];

        foreach ($recentOuts as $out) {
            if ($out->qty_30d <= 0) {
                continue;
            }

            // 7-day daily average vs 30-day daily average
            $daily7d = $out->qty_7d / 7;
            $daily30d = $out->qty_30d / 30;

            if ($daily30d <= 0) {
                continue;
            }

            $ratio = $daily7d / $daily30d;

            if ($ratio >= 2.0) {
                $stock = CurrentStock::query()
                    ->where('product_id', $out->product_id)
                    ->where('location_id', $out->location_id)
                    ->with(['product', 'location'])
                    ->first();

                $findings[] = [
                    'check_type' => 'rapid_depletion',
                    'severity' => 'warning',
                    'product_id' => $out->product_id,
                    'location_id' => $out->location_id,
                    'title' => sprintf(
                        'Rapid depletion: %s at %s (%.1f× normal rate)',
                        $stock->product->name ?? 'Product #' . $out->product_id,
                        $stock->location->code ?? 'Location #' . $out->location_id,
                        round($ratio, 1),
                    ),
                    'description' => sprintf(
                        'Product "%s" (SKU: %s) at %s is depleting at %.1f× the 30-day average rate. '
                        . '7-day daily avg: %.1f units/day vs 30-day daily avg: %.1f units/day. '
                        . 'Current on-hand: %.3f units. At the current rate, stock will be depleted in approximately %d days. '
                        . 'Consider expediting reorder.',
                        $stock->product->name ?? 'Unknown',
                        $stock->product->sku ?? 'N/A',
                        $stock->location->name ?? 'Unknown',
                        round($ratio, 1),
                        round($daily7d, 1),
                        round($daily30d, 1),
                        $stock->quantity_on_hand ?? 0,
                        $daily7d > 0 ? (int) (($stock->quantity_on_hand ?? 0) / $daily7d) : 0,
                    ),
                    'dedup_hash' => md5("rapid_depletion:{$out->product_id}:{$out->location_id}"),
                ];
            }
        }

        return $findings;
    }
}
