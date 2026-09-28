<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Support\ImageResizer;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
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
