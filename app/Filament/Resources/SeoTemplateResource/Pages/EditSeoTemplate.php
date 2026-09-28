<?php

namespace App\Filament\Resources\SeoTemplateResource\Pages;

use App\Filament\Resources\SeoTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeoTemplate extends EditRecord
{
    protected static string $resource = SeoTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
