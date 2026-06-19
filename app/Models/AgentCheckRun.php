<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCheckRun extends Model
{
    protected $fillable = [
        'check_type',
        'started_at',
        'completed_at',
        'findings_count',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
