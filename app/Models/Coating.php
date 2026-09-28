<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOnSlugChange;
use Illuminate\Database\Eloquent\Model;

class Coating extends Model
{
    use RedirectsOnSlugChange;

    public function publicPathForSlug(string $slug): string
    {
        return '/pokrytiya/'.$slug.'.html';
    }

    protected $fillable = [
        'slug',
        'name',
        'description_html',
        'seo_title',
        'seo_description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
