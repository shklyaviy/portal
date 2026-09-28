<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Restores database/snapshot/*.json produced by content:export-snapshot.
 * Rows are upserted by primary key, so the command is idempotent.
 */
class ImportContentSnapshot extends Command
{
    protected $signature = 'content:import-snapshot
                            {--path= : Source directory (default: database/snapshot)}
                            {--truncate : Empty target tables before import}';

    protected $description = 'Import content snapshot (database/snapshot/*.json) into the current database';

    public function handle(): int
    {
        $dir = rtrim($this->option('path') ?: database_path('snapshot'), '/');
        if (! is_dir($dir)) {
            $this->error("Snapshot directory not found: {$dir}");

            return self::FAILURE;
        }

        $tables = ExportContentSnapshot::tables();
        $summary = [];

        Schema::disableForeignKeyConstraints();

        try {
            if ($this->option('truncate')) {
                foreach (array_reverse($tables) as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
            }

            foreach ($tables as $table) {
                $file = "{$dir}/{$table}.json";
                if (! File::exists($file) || ! Schema::hasTable($table)) {
                    $summary[] = [$table, 'skipped'];
                    continue;
                }

                $rows = json_decode(File::get($file), true);
                if (! is_array($rows)) {
                    $summary[] = [$table, 'invalid json'];
                    continue;
                }

                $columns = Schema::getColumnListing($table);
                $count = 0;

                foreach (array_chunk($rows, 200) as $chunk) {
                    $prepared = [];
                    foreach ($chunk as $row) {
                        if (! is_array($row) || ! isset($row['id'])) {
                            continue;
                        }
                        $clean = [];
                        foreach ($columns as $column) {
                            $value = $row[$column] ?? null;
                            if (is_array($value)) {
                                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                            }
                            $clean[$column] = $value;
                        }
                        $prepared[] = $clean;
                    }

                    if ($prepared === []) {
                        continue;
                    }

                    $update = array_values(array_diff($columns, ['id']));
                    DB::table($table)->upsert($prepared, ['id'], $update);
                    $count += count($prepared);
                }

                $summary[] = [$table, $count];
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->table(['table', 'rows'], $summary);
        $this->info('Snapshot imported.');

        return self::SUCCESS;
    }
}
