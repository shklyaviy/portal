<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneCExchangeTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/1c/exchange';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.onec.token' => 'test-token',
            'services.onec.user' => '1c',
            'services.onec.password' => 'secret',
        ]);
    }

    /** @return array<string, string> */
    private function tokenHeaders(): array
    {
        return ['x-1c-token' => 'test-token', 'Accept' => 'application/json'];
    }

    /** @return array<string, mixed> */
    private function samplePayload(): array
    {
        return [
            'sync' => ['mode' => 'full', 'batchId' => 'batch-1'],
            'priceTypes' => [
                ['externalId' => 'retail', 'name' => 'Розничная', 'isDefault' => true],
            ],
            'categories' => [
                ['externalId' => 'cat-root', 'name' => 'Кровля', 'slug' => 'krovlya'],
                ['externalId' => 'cat-child', 'name' => 'Металлочерепица', 'parentExternalId' => 'cat-root'],
            ],
            'products' => [
                [
                    'externalId' => 'p-1',
                    'name' => 'Металлочерепица Monterrey',
                    'sku' => 'MT-1',
                    'categoryExternalId' => 'cat-child',
                    'price' => 689,
                    'unit' => 'м²',
                    'description' => 'Описание из 1С',
                    'attributes' => [
                        ['name' => 'Толщина', 'value' => '0.5 мм'],
                    ],
                    'prices' => [
                        ['priceTypeExternalId' => 'retail', 'amount' => 689],
                    ],
                ],
            ],
        ];
    }

    public function test_rejects_requests_without_credentials(): void
    {
        $this->postJson(self::ENDPOINT, $this->samplePayload())
            ->assertStatus(401);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_accepts_basic_auth(): void
    {
        $this->withBasicAuth('1c', 'secret')
            ->postJson(self::ENDPOINT, $this->samplePayload())
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_rejects_broken_payload_and_logs_error(): void
    {
        $this->call('POST', self::ENDPOINT, [], [], [], [
            'HTTP_X_1C_TOKEN' => 'test-token',
            'CONTENT_TYPE' => 'application/json',
        ], '{"products": [ {broken json')
            ->assertStatus(400)
            ->assertJsonPath('ok', false);

        $this->postJson(self::ENDPOINT, ['foo' => 'bar'], $this->tokenHeaders())
            ->assertStatus(400)
            ->assertJsonPath('ok', false);

        $this->postJson(self::ENDPOINT, [
            'products' => [['name' => 'Без externalId']],
        ], $this->tokenHeaders())
            ->assertStatus(400)
            ->assertJsonFragment(['error' => 'products[0] requires externalId and name']);

        $this->assertDatabaseCount('products', 0);
        $this->assertTrue(SyncLog::query()->where('status', 'error')->exists());
    }

    public function test_full_import_creates_catalog_and_logs_success(): void
    {
        $response = $this->postJson(self::ENDPOINT, $this->samplePayload(), $this->tokenHeaders());

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('stats.categories', 2)
            ->assertJsonPath('stats.products', 1);

        $root = Category::query()->where('external_id', 'cat-root')->firstOrFail();
        $child = Category::query()->where('external_id', 'cat-child')->firstOrFail();
        $this->assertSame('krovlya', $root->slug);
        $this->assertSame($root->id, $child->parent_id);

        $product = Product::query()->where('external_id', 'p-1')->firstOrFail();
        $this->assertSame($child->id, $product->category_id);
        $this->assertSame('689.00', (string) $product->price);
        $this->assertSame('Описание из 1С', $product->description_html);
        $this->assertCount(1, $product->prices);
        $this->assertCount(1, $product->attributeValues);

        $log = SyncLog::query()->latest('id')->firstOrFail();
        $this->assertSame('success', $log->status);
        $this->assertSame('full', $log->mode);
        $this->assertSame('batch-1', $log->batch_id);
    }

    public function test_incremental_import_updates_prices_but_keeps_admin_content_and_urls(): void
    {
        $this->postJson(self::ENDPOINT, $this->samplePayload(), $this->tokenHeaders())->assertOk();

        // Editor fills content in admin.
        $product = Product::query()->where('external_id', 'p-1')->firstOrFail();
        $product->update([
            'description_html' => '<p>Текст из админки</p>',
            'seo_title' => 'SEO из админки',
            'name' => 'Название из админки',
            'name_locked' => true,
            'is_published' => false,
        ]);
        $originalSlug = $product->slug;

        // 1C sends only the changed product with new price, new name, new description and new slug.
        $delta = [
            'sync' => ['mode' => 'incremental', 'batchId' => 'batch-2'],
            'products' => [
                [
                    'externalId' => 'p-1',
                    'name' => 'Новое имя из 1С',
                    'slug' => 'drugoj-slug',
                    'categoryExternalId' => 'cat-child',
                    'price' => 750,
                    'description' => 'Новое описание из 1С',
                    'published' => true,
                    'prices' => [['priceTypeExternalId' => 'retail', 'amount' => 750]],
                ],
            ],
        ];

        $this->postJson(self::ENDPOINT, $delta, $this->tokenHeaders())
            ->assertOk()
            ->assertJsonPath('stats.products', 1);

        $product->refresh();
        $this->assertSame('750.00', (string) $product->price, 'price is synced');
        $this->assertSame('<p>Текст из админки</p>', $product->description_html, 'admin description is preserved');
        $this->assertSame('SEO из админки', $product->seo_title, 'admin SEO is preserved');
        $this->assertSame('Название из админки', $product->name, 'locked name is preserved');
        $this->assertSame($originalSlug, $product->slug, 'URL never changes on sync');
        $this->assertFalse($product->is_published, 'visibility is managed on the site');

        // Nothing was deleted: categories from the first batch still exist.
        $this->assertDatabaseCount('categories', 2);
        $this->assertSame('incremental', SyncLog::query()->latest('id')->value('mode'));
    }

    public function test_sync_is_idempotent(): void
    {
        $this->postJson(self::ENDPOINT, $this->samplePayload(), $this->tokenHeaders())->assertOk();
        $this->postJson(self::ENDPOINT, $this->samplePayload(), $this->tokenHeaders())->assertOk();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('product_prices', 1);
        $this->assertDatabaseCount('product_attribute_values', 1);
    }
}
