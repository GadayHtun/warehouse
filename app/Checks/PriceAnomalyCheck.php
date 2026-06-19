<?php

namespace App\Checks;

use App\Contracts\AgentCheck;
use App\Models\Product;

/**
 * Detects products where cost price changed by more than 30% in a single edit.
 * Triggered on product price change events.
 * Severity: info.
 */
class PriceAnomalyCheck implements AgentCheck
{
    private const PRICE_CHANGE_THRESHOLD_PCT = 30.0;

    /**
     * Optional: if a specific product ID is passed, check only that product.
     * Otherwise, checks all products against their last known price (from audit log).
     */
    public function __construct(
        private ?int $specificProductId = null,
        private ?float $newCostPrice = null,
    ) {}

    public function run(): array
    {
        $findings = [];

        if ($this->specificProductId && $this->newCostPrice !== null) {
            $product = Product::find($this->specificProductId);
            if ($product) {
                $oldPrice = $product->getOriginal('cost_price') ?? $product->cost_price;
                $pctChange = $oldPrice > 0 ? abs(($this->newCostPrice - $oldPrice) / $oldPrice) * 100 : 0;

                if ($pctChange > self::PRICE_CHANGE_THRESHOLD_PCT) {
                    $findings[] = [
                        'check_type' => 'price_anomaly',
                        'severity' => 'info',
                        'product_id' => $product->id,
                        'location_id' => null,
                        'title' => sprintf(
                            'Price anomaly: %s cost changed %.1f%%',
                            $product->name,
                            round($pctChange, 1),
                        ),
                        'description' => sprintf(
                            'Product "%s" (SKU: %s) cost price changed by %.1f%% in a single edit: '
                            . 'from $%.2f to $%.2f. Verify that this price change is correct and authorized. '
                            . 'Large price swings may indicate data entry error.',
                            $product->name,
                            $product->sku ?? 'N/A',
                            round($pctChange, 1),
                            $oldPrice,
                            $this->newCostPrice,
                        ),
                        'dedup_hash' => md5("price_anomaly:{$product->id}:{$this->newCostPrice}"),
                    ];
                }
            }
        }

        return $findings;
    }
}
