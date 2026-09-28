<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Dumps site content (catalog, pages, SEO, redirects) into DB-agnostic JSON files
 * so the same content can be restored on MySQL/SQLite with content:import-snapshot.
 *
 * Users, leads, sync logs and media are intentionally excluded.
 */
class ExportContentSnapshot extends Command
{
    protected $signature = 'content:export-snapshot
                            {--path= : Target directory (default: database/snapshot)}';

    protected $description = 'Export content tables to database/snapshot/*.json (DB-agnostic handover dump)';

    /** @return list<string> */
    public static function tables(): array
    {
        // Order matters for import (FK parents first).
        return [
            'price_types',
            'categories',
            'products',
            'product_prices',
            'product_attributes',
            'product_attribute_values',
            'seo_templates',
            'redirects',
            'pages',
            'news',
            'projects',
            'coatings',
        ];
    }

    public function handle(): int
    {
        $dir = $this->option('path') ?: database_path('snapshot');
        File::ensureDirectoryExists($dir);

        $summary = [];
        foreach (self::tables() as $table) {
            $rows = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
            File::put(
                rtrim($dir, '/').'/'.$table.'.json',
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
            );
            $summary[] = [$table, count($rows)];
        }

        File::put(rtrim($dir, '/').'/README.md', $this->readme());

        $this->table(['table', 'rows'], $summary);
        $this->info("Snapshot written to {$dir}");

        return self::SUCCESS;
    }

    private function readme(): string
    {
        return <<<'MD'
# Снимок контента

JSON-дамп контентных таблиц (каталог, страницы, SEO, редиректы).
Создаётся командой `php artisan content:export-snapshot`, восстанавливается
`php artisan content:import-snapshot` на любой БД (MySQL / SQLite).

Не содержит пользователей, заявок, логов синхронизации и медиа.
MD;
    }
}
