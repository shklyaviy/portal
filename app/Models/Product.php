<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOnSlugChange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RedirectsOnSlugChange;

    public function publicPathForSlug(string $slug): string
    {
        return '/catalog/'.$slug.'.html';
    }

    protected $fillable = [
        'external_id',
        'sku',
        'slug',
        'name',
        'category_id',
        'price',
        'currency',
        'unit',
        'is_in_stock',
        'is_published',
        'show_price',
        'show_availability',
        'sort_order',
        'description_html',
        'seo_title',
        'seo_description',
        'seo_h1',
        'image_path',
        'name_locked',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_in_stock' => 'boolean',
            'is_published' => 'boolean',
            'show_price' => 'boolean',
            'show_availability' => 'boolean',
            'sort_order' => 'integer',
            'name_locked' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function registerMediaCollections(): void
    {
        // Conversions skipped: intervention/image is not required/installed.
        $this->addMediaCollection('images');
    }
}
