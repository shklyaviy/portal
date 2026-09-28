<?php

namespace App\Http\Controllers;

use App\Models\Coating;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Support\HtmlContent;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $page = Page::query()->where('slug', 'about')->where('is_published', true)->first();

        return view('pages.simple', [
            'title' => $page?->title ?? 'О компании',
            'bodyHtml' => $page?->body_html ?? '<p>Кровельный центр «Портал» — материалы и комплектация кровли.</p>',
            'seoTitle' => $page?->seo_title ?? 'О компании',
            'seoDescription' => $page?->seo_description,
        ]);
    }

    public function contacts(): View
    {
        $page = Page::query()->where('slug', 'contacts')->where('is_published', true)->first();

        return view('pages.contacts', [
            'title' => $page?->title ?? 'Контакты',
            'bodyHtml' => $page?->body_html,
            'seoTitle' => $page?->seo_title ?? 'Контакты',
            'seoDescription' => $page?->seo_description,
        ]);
    }

    public function newsIndex(): View
    {
        $items = News::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('pages.simple', [
            'title' => 'Новости и акции',
            'bodyHtml' => view('pages._news-list', ['items' => $items])->render(),
            'seoTitle' => 'Новости и акции',
            'seoDescription' => 'Новости и акции Portalfirma.',
        ]);
    }

    public function newsShow(string $slug): View
    {
        $item = News::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('pages.news-show', [
            'item' => $item,
            'title' => $item->title,
            'bodyHtml' => $item->body_html,
            'seoTitle' => $item->seo_title ?: $item->title,
            'seoDescription' => $item->seo_description,
        ]);
    }

    public function projectsIndex(): View
    {
        $items = Project::query()
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        return view('pages.projects-index', [
            'items' => $items,
            'seoTitle' => 'Наши объекты',
            'seoDescription' => 'Реализованные объекты кровельного центра «Портал».',
        ]);
    }

    public function projectShow(string $slug): View
    {
        $item = Project::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('pages.project-show', [
            'item' => $item,
            'seoTitle' => $item->seo_title ?: $item->title,
            'seoDescription' => $item->seo_description,
        ]);
    }

    public function coatingsIndex(): View
    {
        $items = Coating::query()
            ->where('is_published', true)
            ->where('slug', '!=', 'index')
            ->orderBy('name')
            ->get();

        return view('pages.coatings-index', [
            'items' => $items,
            'seoTitle' => 'Покрытия',
            'seoDescription' => 'Покрытия металлочерепицы и профнастила.',
        ]);
    }

    public function coatingShow(string $slug): View
    {
        $item = Coating::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        $content = HtmlContent::splitMedia((string) $item->description_html);

        return view('pages.coating-show', [
            'item' => $item,
            'images' => $content['images'],
            'bodyHtml' => $content['body'],
            'lead' => $content['lead'],
            'seoTitle' => $item->seo_title ?: $item->name,
            'seoDescription' => $item->seo_description,
        ]);
    }

    public function service(string $slug): View
    {
        if ($slug === 'kalkulyator-rascheta-krovli') {
            return view('pages.calculator', [
                'seoTitle' => 'Калькулятор расчёта кровли',
                'seoDescription' => 'Онлайн-калькулятор кровли: площадь, листы и ориентировочная смета.',
                'h1' => 'Калькулятор расчёта кровли',
            ]);
        }

        $page = Page::query()->where('slug', 'service/'.$slug)->where('is_published', true)->first();

        return view('pages.simple', [
            'title' => $page?->title ?? str_replace('-', ' ', $slug),
            'bodyHtml' => $page?->body_html ?? '<p>Страница услуги.</p>',
            'seoTitle' => $page?->seo_title ?? ($page?->title ?? 'Услуги'),
            'seoDescription' => $page?->seo_description,
        ]);
    }
}
