<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOnSlugChange;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use RedirectsOnSlugChange;

    public function publicPathForSlug(string $slug): string
    {
        return '/projects/'.$slug.'.html';
    }

    protected $fillable = [
        'slug',
        'title',
        'description_html',
        'image_path',
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
