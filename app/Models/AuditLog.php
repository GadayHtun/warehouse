<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only audit trail — no UPDATE or DELETE operations allowed.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // Append-only

    protected $fillable = [
        'user_id',
        'event',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
