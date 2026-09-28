<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves the handover snapshot (database/snapshot) restores into a fresh database
 * and the storefront renders from it.
 */
class ContentSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_imports_into_fresh_database_and_storefront_renders(): void
    {
        $this->artisan('content:import-snapshot')->assertSuccessful();

        $this->assertGreaterThan(100, Category::query()->count());
        $this->assertTrue(Page::query()->where('slug', 'about')->exists());
        $this->assertTrue(Category::query()->where('slug', 'krovlya')->whereNull('parent_id')->exists());

        // Import again: idempotent, no duplicates.
        $count = Category::query()->count();
        $this->artisan('content:import-snapshot')->assertSuccessful();
        $this->assertSame($count, Category::query()->count());

        foreach ([
            '/',
            '/catalog.html',
            '/catalog/krovlya.html',
            '/catalog/fasad.html',
            '/pokrytiya/',
            '/pokrytiya/atlas.html',
            '/projects.html',
            '/about.html',
            '/contacts.html',
            '/service/kalkulyator-rascheta-krovli.html',
            '/sitemap.xml',
            '/robots.txt',
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/robots.txt')->assertSee('Sitemap:');
        $this->get('/sitemap.xml')->assertSee('/catalog/krovlya.html');
        $this->get('/catalog/search.json?q=кров')->assertOk()->assertJsonStructure(['categories', 'products']);
    }
}
