<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoatingResource\Pages;
use App\Models\Coating;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CoatingResource extends Resource
{
    protected static ?string $model = Coating::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Контент';

    protected static ?string $modelLabel = 'Покрытие';

    protected static ?string $pluralModelLabel = 'Покрытия';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->required()->maxLength(255),
            Forms\Components\RichEditor::make('description_html')->label('Описание')->columnSpanFull(),
            Forms\Components\TextInput::make('seo_title')->maxLength(255),
            Forms\Components\Textarea::make('seo_description')->rows(3),
            Forms\Components\Toggle::make('is_published')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->toggleable(),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoatings::route('/'),
            'create' => Pages\CreateCoating::route('/create'),
            'edit' => Pages\EditCoating::route('/{record}/edit'),
        ];
    }
}
