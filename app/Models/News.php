<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOnSlugChange;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use RedirectsOnSlugChange;

    public function publicPathForSlug(string $slug): string
    {
        return '/news/'.$slug.'.html';
    }

    protected $table = 'news';

    protected $fillable = [
        'slug',
        'title',
        'body_html',
        'image_path',
        'seo_title',
        'seo_description',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
