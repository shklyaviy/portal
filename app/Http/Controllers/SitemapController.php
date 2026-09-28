<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Coating;
use App\Models\News;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            url('/'),
            url('/catalog.html'),
            url('/about.html'),
            url('/contacts.html'),
            url('/news.html'),
            url('/projects.html'),
            url('/pokrytiya/'),
            url('/service/kalkulyator-rascheta-krovli.html'),
        ];

        foreach (Page::query()->where('is_published', true)->cursor() as $page) {
            $urls[] = url('/'.ltrim($page->slug, '/').'.html');
        }

        foreach (Category::query()->where('is_published', true)->cursor() as $category) {
            $urls[] = url('/catalog/'.$category->slug.'.html');
        }

        foreach (Product::query()->where('is_published', true)->cursor() as $product) {
            $urls[] = url('/catalog/'.$product->slug.'.html');
        }

        foreach (Coating::query()->where('is_published', true)->where('slug', '!=', 'index')->cursor() as $coating) {
            $urls[] = url('/pokrytiya/'.$coating->slug.'.html');
        }

        foreach (News::query()->where('is_published', true)->cursor() as $news) {
            $urls[] = url('/news/'.$news->slug.'.html');
        }

        foreach (Project::query()->where('is_published', true)->cursor() as $project) {
            $urls[] = url('/projects/'.$project->slug.'.html');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach (array_unique($urls) as $loc) {
            $xml .= '  <url><loc>'.e($loc).'</loc></url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
