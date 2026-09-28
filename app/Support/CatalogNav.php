<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;

class CatalogNav
{
    /** @return list<string> */
    public static function preferredRootOrder(): array
    {
        return [
            'krovlya',
            'fasad',
            'karniznye-svesy-krovli',
            'vodostochnye-sistemy',
            'elementy-bezopasnosti-krovli',
            'dobornye-elementy-krovli',
            'mansardnye-okna',
            'podvesnoj-potolok',
            'cherdachnye-lestnitsy',
            'zabory-o-ograzhdeniya',
            'zabor-zhalyuzi',
            'gidro-paroizolyatsionnye-plenki',
            'teploizolyatsiya',
            'sendvich-paneli',
            'polikarbonat',
            'keramicheskaya-cherepitsa',
            'volnovaya-cherepitsa',
            'soputstvuyushchie-tovary',
        ];
    }

    public static function sortRoots(Collection $roots): Collection
    {
        $order = self::preferredRootOrder();

        return $roots->sortBy(function (Category $category) use ($order) {
            $idx = array_search($category->slug, $order, true);

            return $idx === false ? 1000 + $category->sort_order : $idx;
        })->values();
    }

    public static function rootOf(Category $category): Category
    {
        $current = $category;
        while ($current->parent_id) {
            $parent = $current->relationLoaded('parent')
                ? $current->parent
                : $current->parent()->first();
            if (! $parent) {
                break;
            }
            $current = $parent;
        }

        return $current;
    }

    /** @return Collection<int, Category> */
    public static function ancestors(Category $category): Collection
    {
        $chain = collect();
        $current = $category->relationLoaded('parent') ? $category->parent : $category->parent()->first();
        while ($current) {
            $chain->prepend($current);
            $current = $current->relationLoaded('parent')
                ? $current->parent
                : $current->parent()->first();
        }

        return $chain;
    }

    /** @return list<int> */
    public static function activeIds(Category $category): array
    {
        return self::ancestors($category)
            ->pluck('id')
            ->push($category->id)
            ->all();
    }

    public static function treeForRoot(Category $root): Category
    {
        return Category::query()
            ->where('id', $root->id)
            ->with([
                'children' => fn ($q) => $q
                    ->where('is_published', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with([
                        'children' => fn ($qq) => $qq
                            ->where('is_published', true)
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->with([
                                'children' => fn ($qqq) => $qqq
                                    ->where('is_published', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name'),
                            ]),
                    ]),
            ])
            ->firstOrFail();
    }

    /** @return Collection<int, Category> */
    public static function forest(): Collection
    {
        $childrenWith = [
            'children' => fn ($q) => $q
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with([
                    'children' => fn ($qq) => $qq
                        ->where('is_published', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->with([
                            'children' => fn ($qqq) => $qqq
                                ->where('is_published', true)
                                ->orderBy('sort_order')
                                ->orderBy('name'),
                        ]),
                ]),
        ];

        $roots = Category::query()
            ->whereNull('parent_id')
            ->where('is_published', true)
            ->where('slug', 'not like', '%/%')
            ->with($childrenWith)
            ->get();

        return self::sortRoots($roots);
    }

    public static function nodeShouldOpen(Category $node, array $activeIds): bool
    {
        if (in_array($node->id, $activeIds, true)) {
            return true;
        }

        foreach ($node->children as $child) {
            if (self::nodeShouldOpen($child, $activeIds)) {
                return true;
            }
        }

        return false;
    }
}

