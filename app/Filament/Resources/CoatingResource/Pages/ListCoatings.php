<?php

namespace App\Filament\Resources\CoatingResource\Pages;

use App\Filament\Resources\CoatingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoatings extends ListRecords
{
    protected static string $resource = CoatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
