<?php

namespace App\Checks;

use App\Contracts\AgentCheck;
use App\Models\CurrentStock;

/**
 * Detects any product-location where on-hand quantity has gone negative.
 * Indicates a system bug or bypassed validation.
 * Severity: critical. Frequency: hourly.
 */
class NegativeStockCheck implements AgentCheck
{
    public function run(): array
    {
        $negatives = CurrentStock::query()
            ->where('quantity_on_hand', '<', 0)
            ->with(['product', 'location'])
            ->get();

        $findings = [];

        foreach ($negatives as $stock) {
            $findings[] = [
                'check_type' => 'negative_stock',
                'severity' => 'critical',
                'product_id' => $stock->product_id,
                'location_id' => $stock->location_id,
                'title' => sprintf(
                    'Negative stock: %s at %s',
                    $stock->product->name ?? 'Product #' . $stock->product_id,
                    $stock->location->code ?? 'Location #' . $stock->location_id,
                ),
                'description' => sprintf(
                    'Product "%s" (SKU: %s) has a negative on-hand quantity of %.3f at %s (%s). '
                    . 'This indicates a stock-out was recorded before sufficient stock-in, '
                    . 'or a system bug allowed inventory to go below zero. Immediate investigation required.',
                    $stock->product->name ?? 'Unknown',
                    $stock->product->sku ?? 'N/A',
                    $stock->quantity_on_hand,
                    $stock->location->name ?? 'Unknown',
                    $stock->location->code ?? 'N/A',
                ),
                'dedup_hash' => md5("negative_stock:{$stock->product_id}:{$stock->location_id}"),
            ];
        }

        return $findings;
    }
}
