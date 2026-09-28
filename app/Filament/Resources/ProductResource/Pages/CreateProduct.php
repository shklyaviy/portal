<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Support\ImageResizer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $path = $this->record->image_path;
        if (! is_string($path) || $path === '') {
            return;
        }

        $absolute = Storage::disk('public')->path($path);
        if (is_file($absolute)) {
            ImageResizer::process($absolute, 1600, 400);
        }
    }
}
