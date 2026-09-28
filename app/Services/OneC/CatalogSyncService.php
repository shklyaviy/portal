<?php

namespace App\Services\OneC;

use App\Models\Category;
use App\Models\PriceType;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductPrice;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;

class CatalogSyncService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{categories: int, products: int, prices: int, attributes: int, priceTypes: int}
     */
    public function upsertCatalog(array $payload): array
    {
        $stats = [
            'categories' => 0,
            'products' => 0,
            'prices' => 0,
            'attributes' => 0,
            'priceTypes' => 0,
        ];

        /** @var array<string, int> $priceTypeIds */
        $priceTypeIds = [];
        /** @var array<string, int> $categoryIds */
        $categoryIds = [];

        foreach ($payload['priceTypes'] ?? [] as $pt) {
            if (! is_array($pt) || empty($pt['externalId']) || empty($pt['name'])) {
                continue;
            }

            DB::transaction(function () use ($pt, &$priceTypeIds, &$stats) {
                $row = PriceType::query()->updateOrCreate(
                    ['external_id' => (string) $pt['externalId']],
                    [
                        'name' => (string) $pt['name'],
                        'is_default' => (bool) ($pt['isDefault'] ?? false),
                    ]
                );
                $priceTypeIds[(string) $pt['externalId']] = $row->id;
                $stats['priceTypes']++;
            });
        }

        if ($priceTypeIds === []) {
            $retail = PriceType::query()->updateOrCreate(
                ['external_id' => 'retail'],
                ['name' => 'Розничная', 'is_default' => true]
            );
            $priceTypeIds['retail'] = $retail->id;
        }

        $categories = array_values(array_filter(
            $payload['categories'] ?? [],
            fn ($c) => is_array($c) && ! empty($c['externalId']) && ! empty($c['name'])
        ));

        $guard = 0;
        while ($categories !== [] && $guard < 20) {
            $guard++;
            $remaining = [];

            foreach ($categories as $cat) {
                $parentExternalId = $cat['parentExternalId'] ?? null;
                if ($parentExternalId && ! isset($categoryIds[(string) $parentExternalId])) {
                    $parent = Category::query()->where('external_id', (string) $parentExternalId)->first();
                    if ($parent) {
                        $categoryIds[(string) $parentExternalId] = $parent->id;
                    } else {
                        $remaining[] = $cat;
                        continue;
                    }
                }

                DB::transaction(function () use ($cat, &$categoryIds, &$stats) {
                    $this->upsertCategory($cat, $categoryIds);
                    $stats['categories']++;
                });
            }

            if (count($remaining) === count($categories)) {
                foreach ($remaining as $cat) {
                    unset($cat['parentExternalId']);
                    DB::transaction(function () use ($cat, &$categoryIds, &$stats) {
                        $this->upsertCategory($cat, $categoryIds);
                        $stats['categories']++;
                    });
                }
                break;
            }

            $categories = $remaining;
        }

        foreach ($payload['products'] ?? [] as $product) {
            if (! is_array($product) || empty($product['externalId']) || empty($product['name'])) {
                continue;
            }

            DB::transaction(function () use ($product, &$categoryIds, &$priceTypeIds, &$stats) {
                $this->upsertProduct($product, $categoryIds, $priceTypeIds, $stats);
            });
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $cat
     * @param  array<string, int>  $categoryIds
     */
    private function upsertCategory(array $cat, array &$categoryIds): void
    {
        $externalId = (string) $cat['externalId'];
        $existing = Category::query()->where('external_id', $externalId)->first();

        $parentId = null;
        if (! empty($cat['parentExternalId'])) {
            $parentId = $categoryIds[(string) $cat['parentExternalId']]
                ?? Category::query()->where('external_id', (string) $cat['parentExternalId'])->value('id');
        }

        $slug = $existing?->slug
            ?? $this->uniqueSlug(Category::class, (string) ($cat['slug'] ?? Slug::make((string) $cat['name'])));

        $data = [
            'slug' => $slug,
            'name' => (string) $cat['name'],
            'parent_id' => $parentId,
            'sort_order' => (int) ($cat['sortOrder'] ?? $existing?->sort_order ?? 0),
        ];

        if (! $existing) {
            $data['is_published'] = (bool) ($cat['published'] ?? true);
            $data['description_html'] = $this->optionalHtml($cat['description'] ?? null);
            $data['image_path'] = $this->optionalString($cat['imageUrl'] ?? $cat['image_path'] ?? null);
        } else {
            if ($this->isEmptyAdminField($existing->description_html)) {
                $html = $this->optionalHtml($cat['description'] ?? null);
                if ($html !== null) {
                    $data['description_html'] = $html;
                }
            }
            if ($this->isEmptyAdminField($existing->image_path)) {
                $image = $this->optionalString($cat['imageUrl'] ?? $cat['image_path'] ?? null);
                if ($image !== null) {
                    $data['image_path'] = $image;
                }
            }
        }

        $row = Category::query()->updateOrCreate(
            ['external_id' => $externalId],
            $data
        );

        $categoryIds[$externalId] = $row->id;
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<string, int>  $categoryIds
     * @param  array<string, int>  $priceTypeIds
     * @param  array{categories: int, products: int, prices: int, attributes: int, priceTypes: int}  $stats
     */
    private function upsertProduct(array $product, array &$categoryIds, array &$priceTypeIds, array &$stats): void
    {
        $externalId = (string) $product['externalId'];
        $existing = Product::query()->where('external_id', $externalId)->first();

        $categoryId = null;
        if (! empty($product['categoryExternalId'])) {
            $catExt = (string) $product['categoryExternalId'];
            $categoryId = $categoryIds[$catExt]
                ?? Category::query()->where('external_id', $catExt)->value('id');
        }

        $slug = $existing?->slug
            ?? $this->uniqueSlug(Product::class, (string) ($product['slug'] ?? Slug::make((string) $product['name'])));

        $prices = collect($product['prices'] ?? []);
        $retailPrice = $prices->firstWhere('priceTypeExternalId', 'retail');
        $primaryPrice = $product['price']
            ?? (is_array($retailPrice) ? ($retailPrice['amount'] ?? null) : null)
            ?? (is_array($prices->first()) ? ($prices->first()['amount'] ?? null) : null);

        $data = [
            'slug' => $slug,
            'sku' => $this->optionalString($product['sku'] ?? null),
            'category_id' => $categoryId,
            'price' => $primaryPrice,
            'currency' => (string) ($product['currency'] ?? $existing?->currency ?? 'RUB'),
            'unit' => $this->optionalString($product['unit'] ?? null) ?? $existing?->unit,
            'sort_order' => (int) ($product['sortOrder'] ?? $existing?->sort_order ?? 0),
        ];

        // stock from 1C is intentionally ignored

        if (! $existing || ! $existing->name_locked) {
            $data['name'] = (string) $product['name'];
        }

        if (! $existing) {
            $data['is_published'] = (bool) ($product['published'] ?? true);
            $data['description_html'] = $this->optionalHtml($product['description'] ?? null);
            $data['image_path'] = $this->optionalString($product['imageUrl'] ?? $product['image_path'] ?? null);
        } else {
            if ($this->isEmptyAdminField($existing->description_html)) {
                $html = $this->optionalHtml($product['description'] ?? null);
                if ($html !== null) {
                    $data['description_html'] = $html;
                }
            }
            if ($this->isEmptyAdminField($existing->image_path)) {
                $image = $this->optionalString($product['imageUrl'] ?? $product['image_path'] ?? null);
                if ($image !== null) {
                    $data['image_path'] = $image;
                }
            }
            // seo_* never overwritten from 1C when already filled (never set from payload here)
        }

        $row = Product::query()->updateOrCreate(
            ['external_id' => $externalId],
            $data
        );
        $stats['products']++;

        foreach ($product['prices'] ?? [] as $price) {
            if (! is_array($price) || ! isset($price['amount'])) {
                continue;
            }

            $ptExternal = (string) ($price['priceTypeExternalId'] ?? 'retail');
            $priceTypeId = $priceTypeIds[$ptExternal] ?? null;

            if (! $priceTypeId) {
                $pt = PriceType::query()->updateOrCreate(
                    ['external_id' => $ptExternal],
                    [
                        'name' => (string) ($price['priceTypeName'] ?? $ptExternal),
                        'is_default' => $ptExternal === 'retail',
                    ]
                );
                $priceTypeId = $pt->id;
                $priceTypeIds[$ptExternal] = $priceTypeId;
                $stats['priceTypes']++;
            }

            ProductPrice::query()->updateOrCreate(
                [
                    'product_id' => $row->id,
                    'price_type_id' => $priceTypeId,
                ],
                [
                    'amount' => $price['amount'],
                    'currency' => (string) ($price['currency'] ?? $row->currency ?? 'RUB'),
                ]
            );
            $stats['prices']++;
        }

        if (empty($product['prices']) && $primaryPrice !== null) {
            $retailId = $priceTypeIds['retail'] ?? PriceType::query()->where('external_id', 'retail')->value('id');
            if ($retailId) {
                ProductPrice::query()->updateOrCreate(
                    [
                        'product_id' => $row->id,
                        'price_type_id' => $retailId,
                    ],
                    [
                        'amount' => $primaryPrice,
                        'currency' => (string) ($product['currency'] ?? 'RUB'),
                    ]
                );
                $stats['prices']++;
            }
        }

        foreach ($product['attributes'] ?? [] as $attr) {
            if (! is_array($attr) || empty($attr['name']) || ! array_key_exists('value', $attr)) {
                continue;
            }

            $attrModel = $this->resolveAttribute($attr);
            ProductAttributeValue::query()->updateOrCreate(
                [
                    'product_id' => $row->id,
                    'product_attribute_id' => $attrModel->id,
                ],
                ['value' => (string) $attr['value']]
            );
            $stats['attributes']++;
        }
    }

    /**
     * @param  array<string, mixed>  $attr
     */
    private function resolveAttribute(array $attr): ProductAttribute
    {
        if (! empty($attr['externalId'])) {
            $existing = ProductAttribute::query()->where('external_id', (string) $attr['externalId'])->first();

            return ProductAttribute::query()->updateOrCreate(
                ['external_id' => (string) $attr['externalId']],
                [
                    'name' => (string) $attr['name'],
                    'slug' => $existing?->slug
                        ?? $this->uniqueSlug(ProductAttribute::class, Slug::make((string) $attr['name'])),
                ]
            );
        }

        $slug = Slug::make((string) $attr['name']);
        $existing = ProductAttribute::query()->where('slug', $slug)->first();
        if ($existing) {
            return $existing;
        }

        return ProductAttribute::query()->create([
            'name' => (string) $attr['name'],
            'slug' => $this->uniqueSlug(ProductAttribute::class, $slug),
        ]);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function uniqueSlug(string $modelClass, string $base, mixed $excludeId = null): string
    {
        $candidate = $base !== '' ? $base : 'item';
        $n = 2;

        while (true) {
            $query = $modelClass::query()->where('slug', $candidate);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            if (! $query->exists()) {
                return $candidate;
            }
            $candidate = $base.'-'.$n;
            $n++;
        }
    }

    private function isEmptyAdminField(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function optionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function optionalHtml(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
