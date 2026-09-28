<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'title_template',
        'description_template',
        'h1_template',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
