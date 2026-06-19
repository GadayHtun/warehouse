<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationCountLine extends Model
{
    protected $fillable = [
        'session_id',
        'product_id',
        'physical_quantity',
        'system_quantity_at_count',
        'variance',
        'variance_percentage',
        'status',
        'resolution_type',
        'resolution_note',
        'large_variance_approval_status',
        'large_variance_approver_id',
    ];

    protected function casts(): array
    {
        return [
            'physical_quantity' => 'decimal:3',
            'system_quantity_at_count' => 'decimal:3',
            'variance' => 'decimal:3',
            'variance_percentage' => 'decimal:2',
        ];
    }

    public function session()
    {
        return $this->belongsTo(ReconciliationSession::class, 'session_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function largeVarianceApprover()
    {
        return $this->belongsTo(User::class, 'large_variance_approver_id');
    }

    public function adjustment()
    {
        return $this->hasOne(Adjustment::class, 'count_line_id');
    }
}
