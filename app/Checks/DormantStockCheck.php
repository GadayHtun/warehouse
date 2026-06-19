<?php

namespace App\Checks;

use App\Contracts\AgentCheck;
use App\Models\StockMovement;
use App\Models\CurrentStock;

/**
 * Detects products with zero stock-out movements for 30+ days.
 * Indicates potential dead stock that may need write-off or promotion.
 * Severity: info. Frequency: daily.
 */
class DormantStockCheck implements AgentCheck
{
    private const DORMANT_DAYS = 30;

    public function run(): array
    {
        // Products that have stock but no stock-out movement in the dormant period
        $dormantCutoff = now()->subDays(self::DORMANT_DAYS);

        $activeProductIds = StockMovement::query()
            ->where('direction', 'out')
            ->where('created_at', '>=', $dormantCutoff)
            ->distinct()
            ->pluck('product_id');

        $stockWithProduct = CurrentStock::query()
            ->where('quantity_on_hand', '>', 0)
            ->whereNotIn('product_id', $activeProductIds)
            ->with(['product', 'location'])
            ->get();

        $findings = [];

        foreach ($stockWithProduct as $stock) {
            $lastMovement = StockMovement::query()
                ->where('product_id', $stock->product_id)
                ->where('location_id', $stock->location_id)
                ->where('direction', 'out')
                ->orderByDesc('created_at')
                ->first();

            $lastMoved = $lastMovement?->created_at;

            $findings[] = [
                'check_type' => 'dormant_stock',
                'severity' => 'info',
                'product_id' => $stock->product_id,
                'location_id' => $stock->location_id,
                'title' => sprintf(
                    'Dormant stock: %s at %s (%d+ days no movement)',
                    $stock->product->name ?? 'Product #' . $stock->product_id,
                    $stock->location->code ?? 'Location #' . $stock->location_id,
                    self::DORMANT_DAYS,
                ),
                'description' => sprintf(
                    'Product "%s" (SKU: %s) has %.3f units at %s with no stock-out movements since %s '
                    . '(or never). This stock has been dormant for %d+ days and may represent dead inventory. '
                    . 'Consider promotional pricing, transfer to a higher-volume location, or write-off.',
                    $stock->product->name ?? 'Unknown',
                    $stock->product->sku ?? 'N/A',
                    $stock->quantity_on_hand,
                    $stock->location->name ?? 'Unknown',
                    $lastMoved ? $lastMoved->toDateString() : 'never',
                    self::DORMANT_DAYS,
                ),
                'dedup_hash' => md5("dormant_stock:{$stock->product_id}:{$stock->location_id}"),
            ];
        }

        return $findings;
    }
}
