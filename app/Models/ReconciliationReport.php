<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationReport extends Model
{
    protected $fillable = [
        'session_id',
        'file_path',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function session()
    {
        return $this->belongsTo(ReconciliationSession::class, 'session_id');
    }
}
