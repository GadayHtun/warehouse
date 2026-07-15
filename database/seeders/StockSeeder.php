<?php

namespace Database\Seeders;

use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all()->keyBy('sku');
        $locations = Location::all()->keyBy('code');

        // Stock data: [product_sku => [location_code => quantity]]
        $stockData = [
            // Beverages — high volume across warehouses, moderate in stores
            'BEV-001' => ['WH-A' => 2400, 'WH-B' => 1800, 'ST-01' => 350, 'ST-02' => 120],
            'BEV-002' => ['WH-A' => 800,  'WH-B' => 600,  'ST-01' => 180, 'ST-02' => 60],
            'BEV-003' => ['WH-A' => 5000, 'WH-B' => 3200, 'ST-01' => 800, 'ST-02' => 300],
            'BEV-004' => ['WH-A' => 1200, 'WH-B' => 900,  'ST-01' => 200, 'ST-02' => 80],
            'BEV-005' => ['WH-A' => 600,  'WH-B' => 450,  'ST-01' => 150, 'ST-02' => 40],

            // Snacks
            'SNK-001' => ['WH-A' => 1500, 'WH-B' => 1000, 'ST-01' => 280, 'ST-02' => 90],
            'SNK-002' => ['WH-A' => 2000, 'WH-B' => 1400, 'ST-01' => 400, 'ST-02' => 150],
            'SNK-003' => ['WH-A' => 500,  'WH-B' => 350,  'ST-01' => 100, 'ST-02' => 30],
            'SNK-004' => ['WH-A' => 800,  'WH-B' => 600,  'ST-01' => 180, 'ST-02' => 50],

            // Groceries — bulk at warehouses
            'GRC-001' => ['WH-A' => 3000, 'WH-B' => 2200, 'ST-01' => 500, 'ST-02' => 0],
            'GRC-002' => ['WH-A' => 1800, 'WH-B' => 1200, 'ST-01' => 300, 'ST-02' => 0],
            'GRC-003' => ['WH-A' => 1000, 'WH-B' => 800,  'ST-01' => 200, 'ST-02' => 0],
            'GRC-004' => ['WH-A' => 1200, 'WH-B' => 900,  'ST-01' => 250, 'ST-02' => 0],
            'GRC-005' => ['WH-A' => 1600, 'WH-B' => 1100, 'ST-01' => 350, 'ST-02' => 0],
            'GRC-006' => ['WH-A' => 900,  'WH-B' => 700,  'ST-01' => 180, 'ST-02' => 0],

            // Cleaning
            'CLN-001' => ['WH-A' => 600,  'WH-B' => 400,  'ST-01' => 120, 'ST-02' => 40],
            'CLN-002' => ['WH-A' => 2000, 'WH-B' => 1500, 'ST-01' => 350, 'ST-02' => 100],
            'CLN-003' => ['WH-A' => 800,  'WH-B' => 600,  'ST-01' => 150, 'ST-02' => 50],
            'CLN-004' => ['WH-A' => 400,  'WH-B' => 300,  'ST-01' => 80,  'ST-02' => 25],

            // Dairy — perishable, lower stock
            'DAI-001' => ['WH-A' => 300,  'WH-B' => 200,  'ST-01' => 120, 'ST-02' => 40],
        ];

        foreach ($stockData as $sku => $locationsQty) {
            $product = $products[$sku] ?? null;
            if (!$product) continue;

            foreach ($locationsQty as $code => $qty) {
                $location = $locations[$code] ?? null;
                if (!$location) continue;
                if ($qty <= 0) continue;

                CurrentStock::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                    ],
                    [
                        'quantity_on_hand' => $qty,
                    ]
                );
            }
        }
    }
}
