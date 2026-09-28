<?php

namespace Tests\Feature;

use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CoatingResource\Pages\EditCoating;
use App\Filament\Resources\NewsResource\Pages\CreateNews;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\RedirectResource\Pages\CreateRedirect;
use App\Filament\Resources\SeoTemplateResource\Pages\EditSeoTemplate;
use App\Models\Category;
use App\Models\Coating;
use App\Models\Lead;
use App\Models\News;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use App\Models\SeoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * End-to-end: an editor changes content in the Filament admin and the storefront reflects it.
 */
class AdminEditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('content:import-snapshot');
        $this->artisan('db:seed');

        $this->actingAs(User::query()->where('email', 'admin@portalfirma.local')->firstOrFail());
    }

    public function test_static_page_is_editable_and_rendered(): void
    {
        $page = Page::query()->where('slug', 'about')->firstOrFail();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm([
                'title' => 'О компании «Портал»',
                'body_html' => '<p>Мы работаем с 1993 года — текст из админки.</p>',
                'seo_title' => 'О компании — SEO из админки',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/about.html')
            ->assertOk()
            ->assertSee('О компании «Портал»')
            ->assertSee('текст из админки')
            ->assertSee('<title>О компании — SEO из админки', false);
    }

    public function test_product_is_editable_and_storefront_respects_flags(): void
    {
        $product = Product::query()->where('slug', 'krovlya/metallocherepitsa/monterrey-05-ral-8017')->firstOrFail();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'price' => 777,
                'description_html' => '<p>Описание товара из админки.</p>',
                'seo_title' => 'Monterrey — SEO title из админки',
                'show_price' => false,
                'name_locked' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame('777.00', (string) $product->price);
        $this->assertTrue($product->name_locked);

        $response = $this->get('/catalog/krovlya/metallocherepitsa/monterrey-05-ral-8017.html')
            ->assertOk()
            ->assertSee('Описание товара из админки')
            ->assertSee('<title>Monterrey — SEO title из админки', false)
            ->assertSee('Цена по запросу');
        $this->assertStringNotContainsString('777 ₽', $response->getContent());

        // Hiding a product removes it from the storefront but keeps its URL reserved.
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/catalog/krovlya/metallocherepitsa/monterrey-05-ral-8017.html')->assertNotFound();
        $this->get('/catalog/krovlya/metallocherepitsa.html')->assertOk()->assertDontSee('Monterrey 0.5 мм RAL 8017');
    }

    public function test_product_can_be_created_in_admin(): void
    {
        $category = Category::query()->where('slug', 'krovlya/profnastil')->firstOrFail();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Профнастил НС-35 0.5 мм RAL 3005',
                'slug' => 'krovlya/profnastil/ns35-05-ral-3005',
                'sku' => 'NS35-05-3005',
                'category_id' => $category->id,
                'price' => 615,
                'currency' => 'RUB',
                'description_html' => '<p>Создано вручную в админке.</p>',
                'is_published' => true,
                'show_price' => true,
                'show_availability' => true,
                'is_in_stock' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', ['sku' => 'NS35-05-3005']);

        $this->get('/catalog/krovlya/profnastil.html')->assertOk()->assertSee('Профнастил НС-35 0.5 мм RAL 3005');
        $this->get('/catalog/krovlya/profnastil/ns35-05-ral-3005.html')
            ->assertOk()
            ->assertSee('Создано вручную в админке')
            ->assertSee('615 ₽');
        $this->get('/sitemap.xml')->assertSee('/catalog/krovlya/profnastil/ns35-05-ral-3005.html');
    }

    public function test_category_seo_and_price_visibility_are_editable(): void
    {
        $category = Category::query()->where('slug', 'krovlya/metallocherepitsa')->firstOrFail();

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'seo_title' => 'Металлочерепица в Новороссийске — купить',
                'seo_h1' => 'Металлочерепица: цены и наличие',
                'show_prices' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $response = $this->get('/catalog/krovlya/metallocherepitsa.html')
            ->assertOk()
            ->assertSee('<title>Металлочерепица в Новороссийске — купить', false)
            ->assertSee('Металлочерепица: цены и наличие');
        $this->assertStringNotContainsString('689 ₽', $response->getContent(), 'prices hidden for the whole category');
    }

    public function test_coating_project_and_news_are_editable(): void
    {
        $coating = Coating::query()->where('slug', 'atlas')->firstOrFail();
        Livewire::test(EditCoating::class, ['record' => $coating->getRouteKey()])
            ->fillForm(['description_html' => '<p>Atlas — новое описание из админки.</p>'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get('/pokrytiya/atlas.html')->assertOk()->assertSee('новое описание из админки');

        $project = Project::query()->firstOrFail();
        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm(['title' => 'Объект: Новороссийск, ЖК Тест'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get('/projects.html')->assertOk()->assertSee('Объект: Новороссийск, ЖК Тест');

        Livewire::test(CreateNews::class)
            ->fillForm([
                'title' => 'Акция на металлочерепицу',
                'slug' => 'aktsiya-na-metallocherepitsu',
                'body_html' => '<p>Скидка 10% до конца месяца.</p>',
                'is_published' => true,
                'published_at' => now(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertTrue(News::query()->where('slug', 'aktsiya-na-metallocherepitsu')->exists());
        $this->get('/news.html')->assertOk()->assertSee('Акция на металлочерепицу');
        $this->get('/news/aktsiya-na-metallocherepitsu.html')->assertOk()->assertSee('Скидка 10%');
    }

    public function test_redirect_and_seo_template_from_admin(): void
    {
        Livewire::test(CreateRedirect::class)
            ->fillForm([
                'from_path' => '/catalog/staryj-razdel.html',
                'to_path' => '/catalog/krovlya.html',
                'status_code' => 301,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/catalog/staryj-razdel.html')->assertStatus(301)->assertRedirect('/catalog/krovlya.html');

        $template = SeoTemplate::query()->where('key', 'product')->firstOrFail();
        Livewire::test(EditSeoTemplate::class, ['record' => $template->getRouteKey()])
            ->fillForm(['title_template' => 'Купить [name] в Новороссийске — [category]'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/catalog/krovlya/profnastil/c8-07-ral-9003.html')
            ->assertOk()
            ->assertSee('<title>Купить Профнастил С-8 0.7 мм RAL 9003 в Новороссийске — Профнастил', false);
    }

    public function test_lead_form_from_storefront_reaches_admin(): void
    {
        $this->post('/leads', [
            'name' => 'Иван',
            'phone' => '+7 900 000-00-00',
            'comment' => 'Нужен расчёт кровли',
            'source' => 'contacts',
        ])->assertRedirect();

        $this->assertSame(1, Lead::query()->count());
        $this->assertDatabaseHas('leads', ['phone' => '+7 900 000-00-00', 'source' => 'contacts']);
    }
}
