<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOnSlugChange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use RedirectsOnSlugChange;

    public function publicPathForSlug(string $slug): string
    {
        return '/catalog/'.$slug.'.html';
    }

    protected $fillable = [
        'external_id',
        'slug',
        'name',
        'parent_id',
        'description_html',
        'seo_title',
        'seo_description',
        'seo_h1',
        'show_prices',
        'show_availability',
        'is_published',
        'sort_order',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'show_prices' => 'boolean',
            'show_availability' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
