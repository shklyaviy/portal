<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Coating;
use App\Models\Page;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportLegacyContent extends Command
{
    protected $signature = 'content:import-legacy
                            {--path= : Path to legacy JSON content (default: database/legacy-content, fallback ../next-app/content)}
                            {--force : Overwrite existing description_html / body_html}';

    protected $description = 'Import legacy JSON content (old Joomla site export) into Page/Category/Product/Coating/Project models';

    public function handle(): int
    {
        $root = $this->option('path')
            ?: (is_dir(database_path('legacy-content'))
                ? database_path('legacy-content')
                : realpath(base_path('../next-app/content')));

        if (! $root || ! is_dir($root)) {
            $this->error('Content directory not found. Pass --path=...');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $stats = [
            'pages' => 0,
            'categories' => 0,
            'products' => 0,
            'news' => 0,
            'projects' => 0,
            'coatings' => 0,
            'skipped' => 0,
        ];

        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $data = json_decode(File::get($file->getPathname()), true);
            if (! is_array($data)) {
                $stats['skipped']++;
                continue;
            }

            $slugFromPath = preg_replace('/\.json$/', '', $relative) ?: '';

            if (str_starts_with($slugFromPath, 'catalog/')) {
                $catalogSlug = substr($slugFromPath, strlen('catalog/'));
                $this->importCatalogNode($catalogSlug, $data, $force, $stats);
                continue;
            }

            if (str_starts_with($slugFromPath, 'service/')) {
                $this->upsertPage($slugFromPath, $data, $force, $stats);
                continue;
            }

            if (str_starts_with($slugFromPath, 'pokrytiya/')) {
                $coatingSlug = substr($slugFromPath, strlen('pokrytiya/'));
                $this->upsertCoating($coatingSlug, $data, $force, $stats);
                continue;
            }

            if (str_starts_with($slugFromPath, 'projects/') && $slugFromPath !== 'projects') {
                $projectSlug = substr($slugFromPath, strlen('projects/'));
                $this->upsertProject($projectSlug, $data, $force, $stats);
                continue;
            }

            if (in_array($slugFromPath, ['about', 'contacts', 'politika-konfidentsialnosti'], true)
                || str_starts_with($slugFromPath, 'service')) {
                $this->upsertPage($slugFromPath, $data, $force, $stats);
                continue;
            }

            if ($slugFromPath === 'news' || str_starts_with($slugFromPath, 'news/')) {
                // news.json is a listing; per-item news may not exist as separate files
                continue;
            }
        }

        $this->table(array_keys($stats), [array_values($stats)]);
        $this->info('Legacy content import finished.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $stats
     */
    private function importCatalogNode(string $slug, array $data, bool $force, array &$stats): void
    {
        $product = Product::query()->where('slug', $slug)->first();
        if ($product) {
            $html = $this->extractBodyHtml($data);
            $updates = [];
            if ($force || blank($product->description_html)) {
                if ($html !== '') {
                    $updates['description_html'] = $html;
                }
            }
            if ($force || blank($product->seo_title)) {
                $updates['seo_title'] = $data['title'] ?? $product->seo_title;
            }
            if ($force || blank($product->seo_description)) {
                $updates['seo_description'] = $data['description'] ?? $product->seo_description;
            }
            if ($force || blank($product->seo_h1)) {
                $updates['seo_h1'] = $data['h1'] ?? $product->seo_h1;
            }
            if ($updates !== []) {
                $product->fill($updates)->save();
                $stats['products']++;
            } else {
                $stats['skipped']++;
            }

            return;
        }

        $category = Category::query()->where('slug', $slug)->first();
        if ($category) {
            $html = $this->extractBodyHtml($data);
            $updates = [];
            if ($force || blank($category->description_html)) {
                if ($html !== '') {
                    $updates['description_html'] = $html;
                }
            }
            if ($force || blank($category->seo_title)) {
                $updates['seo_title'] = $data['title'] ?? $category->seo_title;
            }
            if ($force || blank($category->seo_description)) {
                $updates['seo_description'] = $data['description'] ?? $category->seo_description;
            }
            if ($force || blank($category->seo_h1)) {
                $updates['seo_h1'] = $data['h1'] ?? $category->seo_h1;
            }
            if ($updates !== []) {
                $category->fill($updates)->save();
                $stats['categories']++;
            } else {
                $stats['skipped']++;
            }

            return;
        }

        // Create category shell if missing (safe upsert for catalog tree)
        $name = (string) ($data['h1'] ?? $data['title'] ?? $slug);
        $existing = Category::query()->where('slug', $slug)->first();
        $html = $this->extractBodyHtml($data);
        Category::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description_html' => ($force || blank($existing?->description_html))
                    ? ($html ?: $existing?->description_html)
                    : $existing?->description_html,
                'seo_title' => $data['title'] ?? $existing?->seo_title,
                'seo_description' => $data['description'] ?? $existing?->seo_description,
                'seo_h1' => $data['h1'] ?? $existing?->seo_h1,
                'is_published' => true,
            ]
        );
        $stats['categories']++;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $stats
     */
    private function upsertPage(string $slug, array $data, bool $force, array &$stats): void
    {
        $page = Page::query()->firstOrNew(['slug' => $slug]);
        $html = $this->extractBodyHtml($data);

        if (! $force && $page->exists && filled($page->body_html)) {
            $stats['skipped']++;

            return;
        }

        $page->fill([
            'title' => (string) ($data['h1'] ?? $data['title'] ?? $slug),
            'body_html' => $html !== '' ? $html : $page->body_html,
            'seo_title' => $data['title'] ?? $page->seo_title,
            'seo_description' => $data['description'] ?? $page->seo_description,
            'is_published' => true,
        ])->save();

        $stats['pages']++;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $stats
     */
    private function upsertCoating(string $slug, array $data, bool $force, array &$stats): void
    {
        $row = Coating::query()->firstOrNew(['slug' => $slug]);
        if (! $force && $row->exists && filled($row->description_html)) {
            $stats['skipped']++;

            return;
        }

        $name = (string) ($data['h1'] ?? $data['title'] ?? $slug);
        if ($slug === 'index' || mb_strtolower($name) === 'покрытия') {
            $row->fill([
                'name' => $name,
                'description_html' => $this->extractBodyHtml($data) ?: $row->description_html,
                'seo_title' => $data['title'] ?? $row->seo_title,
                'seo_description' => $data['description'] ?? $row->seo_description,
                'is_published' => false,
            ])->save();
            $stats['coatings']++;

            return;
        }

        $row->fill([
            'name' => $name,
            'description_html' => $this->extractBodyHtml($data) ?: $row->description_html,
            'seo_title' => $data['title'] ?? $row->seo_title,
            'seo_description' => $data['description'] ?? $row->seo_description,
            'is_published' => true,
        ])->save();
        $stats['coatings']++;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $stats
     */
    private function upsertProject(string $slug, array $data, bool $force, array &$stats): void
    {
        $row = Project::query()->firstOrNew(['slug' => $slug]);
        if (! $force && $row->exists && filled($row->description_html) && filled($row->image_path)) {
            $stats['skipped']++;

            return;
        }

        $location = $this->extractProjectLocation($data);
        $gallery = $this->extractProjectGalleryHtml($data, $location);

        $row->fill([
            'title' => $location !== '' ? $location : (string) ($data['h1'] ?? $data['title'] ?? $slug),
            'description_html' => ($force || blank($row->description_html) || ! str_contains((string) $row->description_html, 'pf-gallery'))
                ? ($gallery['html'] !== '' ? $gallery['html'] : ($this->extractBodyHtml($data) ?: $row->description_html))
                : $row->description_html,
            'image_path' => $gallery['cover'] ?: $row->image_path,
            'seo_title' => $location !== ''
                ? 'Наши объекты: '.$location
                : ($data['title'] ?? $row->seo_title),
            'seo_description' => $data['description'] ?? $row->seo_description,
            'is_published' => true,
        ])->save();
        $stats['projects']++;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractProjectLocation(array $data): string
    {
        $desc = trim((string) ($data['description'] ?? ''));
        if ($desc !== '' && preg_match('/:\s*(.+?)\.?\s*$/u', $desc, $m)) {
            return trim($m[1], " .\t");
        }

        $title = (string) ($data['title'] ?? '');
        if (preg_match('/категории:\s*(.+)$/iu', $title, $m)) {
            return trim($m[1]);
        }

        $raw = (string) ($data['contentHtml'] ?? '');
        if ($raw !== '' && preg_match('/headerGallery[\s\S]*?<h2[^>]*>(.*?)<\/h2>/iu', $raw, $m)) {
            return trim(html_entity_decode(strip_tags($m[1])));
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{html: string, cover: ?string}
     */
    private function extractProjectGalleryHtml(array $data, string $location): array
    {
        $raw = (string) ($data['contentHtml'] ?? '');
        $items = [];

        if ($raw !== '') {
            preg_match_all(
                '/href="(https?:\/\/[^"]*joomgallery\/details\/[^"]+\.(?:jpe?g|png|webp))"[^>]*style=[\'"]background:\s*url\(["\']?([^"\')]+)["\']?\)/iu',
                $raw,
                $matches,
                PREG_SET_ORDER
            );
            foreach ($matches as $match) {
                $items[] = [
                    'full' => $match[1],
                    'thumb' => $match[2],
                ];
            }

            if ($items === []) {
                preg_match_all(
                    '/joomgallery\/thumbnails\/([^"\')\s]+\.(?:jpe?g|png|webp))/iu',
                    $raw,
                    $thumbs
                );
                foreach ($thumbs[1] ?? [] as $rel) {
                    $items[] = [
                        'full' => 'https://www.portalfirma.ru/images/joomgallery/details/'.$rel,
                        'thumb' => 'https://www.portalfirma.ru/images/joomgallery/thumbnails/'.$rel,
                    ];
                }
            }
        }

        if ($items === []) {
            return ['html' => '', 'cover' => null];
        }

        $parts = ['<div class="pf-gallery">'];
        foreach ($items as $i => $item) {
            $alt = $location !== '' ? $location.' — фото '.($i + 1) : 'Объект — фото '.($i + 1);
            $parts[] = '<a class="pf-gallery-item" href="'.e($item['full']).'" target="_blank" rel="noopener">'
                .'<img src="'.e($item['thumb']).'" alt="'.e($alt).'" loading="lazy"></a>';
        }
        $parts[] = '</div>';
        $parts[] = '<p>Реализованный объект'.($location !== '' ? ' в локации «'.e($location).'»' : '').'. Материалы и комплектация — кровельный центр «Портал».</p>';

        return [
            'html' => implode("\n", $parts),
            'cover' => $items[0]['thumb'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractBodyHtml(array $data): string
    {
        $raw = (string) ($data['contentHtml'] ?? '');
        if ($raw !== '' && preg_match('/class="pageDecoration"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/is', $raw, $m)) {
            return trim($m[1]);
        }
        if ($raw !== '' && preg_match('/itemprop="description"[^>]*>(.*?)<\/div>/is', $raw, $m)) {
            $chunk = trim($m[1]);
            if ($chunk !== '' && strlen($chunk) < 50000) {
                return $chunk;
            }
        }

        $paragraphs = $data['paragraphs'] ?? [];
        if (is_array($paragraphs) && $paragraphs !== []) {
            $useful = array_values(array_filter($paragraphs, function ($p) {
                $p = trim(html_entity_decode(strip_tags((string) $p)));
                if ($p === '') {
                    return false;
                }
                if (str_contains($p, 'Офис №1') || str_contains($p, 'Политика Конфиденциальности')) {
                    return false;
                }
                if (str_contains($p, 'Создание сайта') || str_contains($p, 'Ассортимент от эконом')) {
                    return false;
                }

                return mb_strlen($p) > 40;
            }));

            if ($useful !== []) {
                return collect($useful)
                    ->take(12)
                    ->map(fn ($p) => '<p>'.e(html_entity_decode(strip_tags((string) $p))).'</p>')
                    ->implode("\n");
            }
        }

        if (! empty($data['description'])) {
            return '<p>'.e((string) $data['description']).'</p>';
        }

        return '';
    }
}
