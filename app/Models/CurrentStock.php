<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Denormalized current stock levels.
 * Updated synchronously within the same DB transaction as inventory_transactions.
 * The inventory_transactions table is the authoritative source.
 */
class CurrentStock extends Model
{
    protected $table = 'current_stock';

    protected $fillable = [
        'product_id',
        'location_id',
        'quantity_on_hand',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:3',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
