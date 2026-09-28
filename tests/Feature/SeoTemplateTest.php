<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SeoTemplate;
use App\Support\SeoResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_bitrix_style_placeholders_are_supported(): void
    {
        SeoTemplate::query()->create([
            'key' => 'product',
            'name' => 'Товар',
            'title_template' => 'Купить [name] в Новороссийске — {category}',
            'description_template' => '%name%, артикул [SKU], цена [price] ₽',
            'h1_template' => '[name]',
            'is_active' => true,
        ]);

        $category = Category::query()->create(['name' => 'Металлочерепица', 'slug' => 'metallocherepitsa']);
        $product = Product::query()->create([
            'name' => 'Monterrey 0.5',
            'slug' => 'metallocherepitsa/monterrey-05',
            'sku' => 'MT-05',
            'price' => 689,
            'category_id' => $category->id,
        ]);

        $seo = SeoResolver::resolve($product->fresh('category'));

        $this->assertSame('Купить Monterrey 0.5 в Новороссийске — Металлочерепица', $seo['title']);
        $this->assertSame('Monterrey 0.5, артикул MT-05, цена 689.00 ₽', $seo['description']);
        $this->assertSame('Monterrey 0.5', $seo['h1']);
    }

    public function test_manual_seo_fields_override_template(): void
    {
        SeoTemplate::query()->create([
            'key' => 'category',
            'name' => 'Категория',
            'title_template' => '[name] — каталог',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'name' => 'Фасады',
            'slug' => 'fasad',
            'seo_title' => 'Фасадные материалы в Новороссийске',
        ]);

        $seo = SeoResolver::resolve($category);

        $this->assertSame('Фасадные материалы в Новороссийске', $seo['title']);
        $this->assertSame('Фасады', $seo['h1']);
    }
}
