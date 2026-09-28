<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogNav;
use App\Support\SeoResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        $sections = CatalogNav::sortRoots(
            Category::query()
                ->whereNull('parent_id')
                ->where('is_published', true)
                ->where('slug', 'not like', '%/%')
                ->withCount([
                    'children' => fn ($q) => $q->where('is_published', true),
                    'products' => fn ($q) => $q->where('is_published', true),
                ])
                ->get()
        );

        return view('catalog.index', [
            'sections' => $sections,
            'navForest' => CatalogNav::forest(),
            'activeIds' => [],
            'seoTitle' => 'Каталог — Кровля, фасады, заборы',
            'seoDescription' => 'Каталог продукции: металлочерепица, профнастил, сайдинг, водосточные системы, заборы, утеплитель. Кровельный центр «Портал».',
            'h1' => 'Каталог продукции',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['categories' => [], 'products' => []]);
        }

        $like = '%'.$q.'%';

        $categories = Category::query()
            ->where('is_published', true)
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $c) => [
                'name' => $c->name,
                'url' => url('/catalog/'.$c->slug.'.html'),
                'type' => 'Раздел',
            ])
            ->values();

        $products = Product::query()
            ->where('is_published', true)
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'slug', 'sku'])
            ->map(fn (Product $p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'url' => url('/catalog/'.$p->slug.'.html'),
                'type' => 'Товар',
            ])
            ->values();

        return response()->json([
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    public function show(string $path): View
    {
        $slug = trim($path, '/');
        $forest = CatalogNav::forest();

        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['category.parent.parent', 'attributeValues.attribute', 'prices.priceType'])
            ->first();

        if ($product) {
            $seo = SeoResolver::resolve($product);
            $activeIds = [];
            $breadcrumbs = collect();

            if ($product->category) {
                $activeIds = CatalogNav::activeIds($product->category);
                $breadcrumbs = CatalogNav::ancestors($product->category)->push($product->category);
            }

            return view('catalog.product', [
                'product' => $product,
                'seoTitle' => $seo['title'],
                'seoDescription' => $seo['description'],
                'h1' => $seo['h1'],
                'navForest' => $forest,
                'activeIds' => $activeIds,
                'breadcrumbs' => $breadcrumbs,
            ]);
        }

        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['parent.parent.parent'])
            ->with([
                'children' => fn ($q) => $q
                    ->where('is_published', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->withCount([
                        'children' => fn ($c) => $c->where('is_published', true),
                        'products' => fn ($p) => $p->where('is_published', true),
                    ]),
                'products' => fn ($q) => $q
                    ->where('is_published', true)
                    ->orderByDesc('is_in_stock')
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->firstOrFail();

        $seo = SeoResolver::resolve($category);
        $activeIds = CatalogNav::activeIds($category);
        $breadcrumbs = CatalogNav::ancestors($category);

        return view('catalog.category', [
            'category' => $category,
            'seoTitle' => $seo['title'],
            'seoDescription' => $seo['description'],
            'h1' => $seo['h1'],
            'navForest' => $forest,
            'activeIds' => $activeIds,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
