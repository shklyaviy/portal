<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_redirect_from_admin_is_applied(): void
    {
        Redirect::query()->create([
            'from_path' => '/catalog/old-section.html',
            'to_path' => '/catalog/krovlya.html',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/catalog/old-section.html')
            ->assertStatus(301)
            ->assertRedirect('/catalog/krovlya.html');
    }

    public function test_inactive_redirect_is_ignored(): void
    {
        Redirect::query()->create([
            'from_path' => '/catalog/old-section.html',
            'to_path' => '/catalog/krovlya.html',
            'is_active' => false,
        ]);

        $this->get('/catalog/old-section.html')->assertStatus(404);
    }

    public function test_changing_product_slug_in_admin_creates_301(): void
    {
        $category = Category::query()->create(['name' => 'Кровля', 'slug' => 'krovlya']);
        $product = Product::query()->create([
            'name' => 'Товар',
            'slug' => 'krovlya/tovar',
            'category_id' => $category->id,
        ]);

        $product->update(['slug' => 'krovlya/tovar-novyj']);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/catalog/krovlya/tovar.html',
            'to_path' => '/catalog/krovlya/tovar-novyj.html',
            'status_code' => 301,
        ]);

        $this->get('/catalog/krovlya/tovar.html')
            ->assertStatus(301)
            ->assertRedirect('/catalog/krovlya/tovar-novyj.html');

        // Renaming back must not create a redirect loop.
        $product->update(['slug' => 'krovlya/tovar']);
        $this->assertDatabaseMissing('redirects', ['from_path' => '/catalog/krovlya/tovar.html']);
        $this->assertDatabaseHas('redirects', [
            'from_path' => '/catalog/krovlya/tovar-novyj.html',
            'to_path' => '/catalog/krovlya/tovar.html',
        ]);
    }

    public function test_changing_category_slug_creates_301(): void
    {
        $category = Category::query()->create(['name' => 'Фасады', 'slug' => 'fasad']);
        $category->update(['slug' => 'fasady']);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/catalog/fasad.html',
            'to_path' => '/catalog/fasady.html',
        ]);
    }
}
