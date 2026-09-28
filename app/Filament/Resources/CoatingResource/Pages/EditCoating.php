<?php

namespace App\Filament\Resources\CoatingResource\Pages;

use App\Filament\Resources\CoatingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoating extends EditRecord
{
    protected static string $resource = CoatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
