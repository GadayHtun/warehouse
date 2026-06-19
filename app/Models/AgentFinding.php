<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentFinding extends Model
{
    protected $fillable = [
        'check_type',
        'severity',
        'product_id',
        'location_id',
        'title',
        'description',
        'detected_at',
        'status',
        'reviewer_id',
        'reviewed_at',
        'review_note',
        'dedup_hash',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}
