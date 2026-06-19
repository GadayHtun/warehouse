<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category',
        'unit_of_measure',
        'min_stock_threshold',
        'reorder_point',
        'cost_price',
        'retail_price',
        'barcode',
    ];

    protected function casts(): array
    {
        return [
            'min_stock_threshold' => 'decimal:3',
            'reorder_point' => 'decimal:3',
            'cost_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
        ];
    }

    public function stockAtLocation(int $locationId): ?CurrentStock
    {
        return $this->currentStock()->where('location_id', $locationId)->first();
    }

    public function currentStock()
    {
        return $this->hasMany(CurrentStock::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
