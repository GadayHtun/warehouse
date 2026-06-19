<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'type',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function reconciliationSessions()
    {
        return $this->hasMany(ReconciliationSession::class);
    }

    public function currentStock()
    {
        return $this->hasMany(CurrentStock::class);
    }
}
