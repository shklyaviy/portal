<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class RebuildCategoryTree extends Command
{
    protected $signature = 'catalog:rebuild-tree';

    protected $description = 'Restore category parent_id hierarchy from slash-separated slugs';

    public function handle(): int
    {
        $bySlug = Category::query()->get()->keyBy('slug');
        $updated = 0;

        foreach ($bySlug as $category) {
            $slug = trim((string) $category->slug, '/');
            if (! str_contains($slug, '/')) {
                if ($category->parent_id !== null) {
                    $category->parent_id = null;
                    $category->save();
                    $updated++;
                }
                continue;
            }

            $parentSlug = substr($slug, 0, (int) strrpos($slug, '/'));
            $parent = $bySlug->get($parentSlug);

            // Walk up until an existing parent is found
            while (! $parent && str_contains($parentSlug, '/')) {
                $parentSlug = substr($parentSlug, 0, (int) strrpos($parentSlug, '/'));
                $parent = $bySlug->get($parentSlug);
            }

            // Fallback: first segment root
            if (! $parent) {
                $rootSlug = explode('/', $slug)[0];
                $parent = $bySlug->get($rootSlug);
            }

            $newParentId = $parent?->id;
            if ($category->parent_id !== $newParentId) {
                $category->parent_id = $newParentId;
                $category->save();
                $updated++;
            }
        }

        $roots = Category::query()->whereNull('parent_id')->where('is_published', true)->count();
        $this->info("Updated {$updated} categories. Published roots: {$roots}");

        return self::SUCCESS;
    }
}
