<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only table — no UPDATE or DELETE operations allowed.
 * Inventory quantity is derived from SUM of these transactions.
 */
class InventoryTransaction extends Model
{
    public const UPDATED_AT = null; // Append-only — no updated_at

    protected $fillable = [
        'product_id',
        'location_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'user_id',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
