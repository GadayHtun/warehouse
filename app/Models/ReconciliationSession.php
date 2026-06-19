<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationSession extends Model
{
    protected $fillable = [
        'location_id',
        'user_id',
        'status',
        'category_filter',
        'notes',
        'started_at',
        'submitted_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function countLines()
    {
        return $this->hasMany(ReconciliationCountLine::class, 'session_id');
    }

    public function reports()
    {
        return $this->hasMany(ReconciliationReport::class, 'session_id');
    }
}
