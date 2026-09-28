<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\SeoTemplate;
use Illuminate\Database\Eloquent\Model;

class SeoResolver
{
    /**
     * @return array{title: string, description: string|null, h1: string}
     */
    public static function resolve(Model $model, ?string $templateKey = null): array
    {
        $key = $templateKey ?? match (true) {
            $model instanceof Product => 'product',
            $model instanceof Category => 'category',
            default => null,
        };

        $name = (string) ($model->getAttribute('name') ?? $model->getAttribute('title') ?? '');
        $sku = (string) ($model->getAttribute('sku') ?? '');
        $price = $model->getAttribute('price');
        $priceStr = $price !== null && $price !== '' ? (string) $price : '';

        $categoryName = '';
        if ($model instanceof Product) {
            $categoryName = (string) ($model->category?->name ?? '');
        } elseif ($model instanceof Category && $model->parent) {
            $categoryName = (string) $model->parent->name;
        }

        $placeholders = self::placeholders([
            'name' => $name,
            'sku' => $sku,
            'category' => $categoryName,
            'price' => $priceStr,
        ]);

        $title = self::filledString($model->getAttribute('seo_title'));
        $description = self::filledString($model->getAttribute('seo_description'));
        $h1 = self::filledString($model->getAttribute('seo_h1'));

        if ($key && (! $title || ! $description || ! $h1)) {
            $template = SeoTemplate::query()
                ->where('key', $key)
                ->where('is_active', true)
                ->first();

            if ($template) {
                $title ??= self::apply($template->title_template, $placeholders) ?: null;
                $description ??= self::apply($template->description_template, $placeholders) ?: null;
                $h1 ??= self::apply($template->h1_template, $placeholders) ?: null;
            }
        }

        return [
            'title' => $title ?: $name,
            'description' => $description,
            'h1' => $h1 ?: $name,
        ];
    }

    /**
     * Supported placeholder syntaxes: {name}, [name], %name% (Bitrix-style).
     *
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    public static function placeholders(array $values): array
    {
        $map = [];
        foreach ($values as $key => $value) {
            $map['{'.$key.'}'] = $value;
            $map['['.$key.']'] = $value;
            $map['%'.$key.'%'] = $value;
            $map['{'.mb_strtoupper($key).'}'] = $value;
            $map['['.mb_strtoupper($key).']'] = $value;
        }

        return $map;
    }

    /** @return list<string> */
    public static function availablePlaceholders(): array
    {
        return ['name', 'sku', 'category', 'price'];
    }

    private static function filledString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    private static function apply(?string $template, array $placeholders): string
    {
        if ($template === null || trim($template) === '') {
            return '';
        }

        return strtr($template, $placeholders);
    }
}
