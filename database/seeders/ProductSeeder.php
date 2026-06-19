<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['sku' => 'BEV-001', 'name' => 'Cola Can 330ml', 'category' => 'Beverages', 'uom' => 'pcs', 'min' => 50, 'reorder' => 200, 'cost' => 0.45, 'retail' => 1.00],
            ['sku' => 'BEV-002', 'name' => 'Orange Juice 1L', 'category' => 'Beverages', 'uom' => 'pcs', 'min' => 30, 'reorder' => 120, 'cost' => 1.20, 'retail' => 2.50],
            ['sku' => 'BEV-003', 'name' => 'Mineral Water 500ml', 'category' => 'Beverages', 'uom' => 'pcs', 'min' => 100, 'reorder' => 500, 'cost' => 0.20, 'retail' => 0.80],
            ['sku' => 'BEV-004', 'name' => 'Energy Drink 250ml', 'category' => 'Beverages', 'uom' => 'pcs', 'min' => 40, 'reorder' => 150, 'cost' => 0.90, 'retail' => 2.00],
            ['sku' => 'BEV-005', 'name' => 'Green Tea 500ml', 'category' => 'Beverages', 'uom' => 'pcs', 'min' => 25, 'reorder' => 100, 'cost' => 0.65, 'retail' => 1.50],

            ['sku' => 'SNK-001', 'name' => 'Potato Chips 150g', 'category' => 'Snacks', 'uom' => 'pcs', 'min' => 40, 'reorder' => 150, 'cost' => 0.80, 'retail' => 1.80],
            ['sku' => 'SNK-002', 'name' => 'Chocolate Bar 100g', 'category' => 'Snacks', 'uom' => 'pcs', 'min' => 60, 'reorder' => 200, 'cost' => 0.55, 'retail' => 1.30],
            ['sku' => 'SNK-003', 'name' => 'Mixed Nuts 200g', 'category' => 'Snacks', 'uom' => 'pcs', 'min' => 20, 'reorder' => 80, 'cost' => 2.50, 'retail' => 4.50],
            ['sku' => 'SNK-004', 'name' => 'Cookies Pack 250g', 'category' => 'Snacks', 'uom' => 'pcs', 'min' => 30, 'reorder' => 100, 'cost' => 1.10, 'retail' => 2.20],

            ['sku' => 'GRC-001', 'name' => 'Rice 5kg Bag', 'category' => 'Groceries', 'uom' => 'pcs', 'min' => 50, 'reorder' => 200, 'cost' => 4.00, 'retail' => 7.50],
            ['sku' => 'GRC-002', 'name' => 'Cooking Oil 1L', 'category' => 'Groceries', 'uom' => 'pcs', 'min' => 40, 'reorder' => 150, 'cost' => 2.80, 'retail' => 5.00],
            ['sku' => 'GRC-003', 'name' => 'Wheat Flour 2kg', 'category' => 'Groceries', 'uom' => 'pcs', 'min' => 30, 'reorder' => 120, 'cost' => 1.60, 'retail' => 3.20],
            ['sku' => 'GRC-004', 'name' => 'Sugar 1kg', 'category' => 'Groceries', 'uom' => 'pcs', 'min' => 35, 'reorder' => 140, 'cost' => 1.20, 'retail' => 2.50],
            ['sku' => 'GRC-005', 'name' => 'Pasta 500g', 'category' => 'Groceries', 'uom' => 'pcs', 'min' => 45, 'reorder' => 180, 'cost' => 0.70, 'retail' => 1.60],
            ['sku' => 'GRC-006', 'name' => 'Canned Tomatoes 400g', 'category' => 'Groceries', 'uom' => 'pcs', 'min' => 25, 'reorder' => 100, 'cost' => 0.95, 'retail' => 2.10],

            ['sku' => 'CLN-001', 'name' => 'Laundry Detergent 2L', 'category' => 'Cleaning', 'uom' => 'pcs', 'min' => 20, 'reorder' => 80, 'cost' => 3.50, 'retail' => 6.50],
            ['sku' => 'CLN-002', 'name' => 'Dish Soap 500ml', 'category' => 'Cleaning', 'uom' => 'pcs', 'min' => 50, 'reorder' => 200, 'cost' => 1.40, 'retail' => 2.90],
            ['sku' => 'CLN-003', 'name' => 'All-Purpose Cleaner 750ml', 'category' => 'Cleaning', 'uom' => 'pcs', 'min' => 30, 'reorder' => 120, 'cost' => 2.10, 'retail' => 4.00],
            ['sku' => 'CLN-004', 'name' => 'Paper Towels 6-Roll', 'category' => 'Cleaning', 'uom' => 'pcs', 'min' => 25, 'reorder' => 100, 'cost' => 4.50, 'retail' => 8.00],

            ['sku' => 'DAI-001', 'name' => 'Fresh Milk 1L', 'category' => 'Dairy', 'uom' => 'L', 'min' => 30, 'reorder' => 120, 'cost' => 1.50, 'retail' => 3.00],
        ];

        foreach ($products as $p) {
            Product::create([
                'sku' => $p['sku'],
                'name' => $p['name'],
                'category' => $p['category'],
                'unit_of_measure' => $p['uom'],
                'min_stock_threshold' => $p['min'],
                'reorder_point' => $p['reorder'],
                'cost_price' => $p['cost'],
                'retail_price' => $p['retail'],
            ]);
        }
    }
}
