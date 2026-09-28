<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('description_html')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_h1')->nullable();
            $table->boolean('show_prices')->default(true);
            $table->boolean('show_availability')->default(true);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->index(['parent_id', 'is_published']);
            $table->index('sort_order');
        });

        Schema::create('price_types', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('sku')->nullable()->index();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 8)->default('RUB');
            $table->string('unit')->nullable();
            $table->boolean('is_in_stock')->default(true);
            $table->boolean('is_published')->default(true);
            $table->boolean('show_price')->default(true);
            $table->boolean('show_availability')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            // Admin-owned content — never overwritten by 1C sync
            $table->longText('description_html')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_h1')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('name_locked')->default(false);
            $table->timestamps();
            $table->index(['category_id', 'is_published']);
            $table->index(['is_in_stock', 'sort_order', 'name']);
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('RUB');
            $table->timestamps();
            $table->unique(['product_id', 'price_type_id']);
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
            $table->unique(['product_id', 'product_attribute_id'], 'pav_product_attr_unique');
        });

        Schema::create('seo_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // product, category
            $table->string('name');
            $table->string('title_template')->nullable();
            $table->text('description_template')->nullable();
            $table->string('h1_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('1c');
            $table->string('mode')->nullable(); // full|incremental
            $table->string('batch_id')->nullable()->index();
            $table->string('status'); // success|error|partial
            $table->text('message')->nullable();
            $table->json('stats')->nullable();
            $table->string('payload_hash')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body_html')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body_html')->nullable();
            $table->string('image_path')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('description_html')->nullable();
            $table->string('image_path')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('coatings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->longText('description_html')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->text('comment')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('coatings');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('news');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('seo_templates');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('price_types');
        Schema::dropIfExists('categories');
    }
};
