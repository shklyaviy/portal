<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'source',
        'mode',
        'batch_id',
        'status',
        'message',
        'stats',
        'payload_hash',
    ];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
        ];
    }
}
