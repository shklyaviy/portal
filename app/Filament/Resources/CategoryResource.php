<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?string $modelLabel = 'Категория';

    protected static ?string $pluralModelLabel = 'Категории';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('external_id')->label('1C ID')->maxLength(255),
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->required()->maxLength(255),
            Forms\Components\Select::make('parent_id')
                ->label('Родитель')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\RichEditor::make('description_html')->label('Описание')->columnSpanFull(),
            Forms\Components\TextInput::make('seo_title')->maxLength(255),
            Forms\Components\Textarea::make('seo_description')->rows(3),
            Forms\Components\TextInput::make('seo_h1')->maxLength(255),
            Forms\Components\TextInput::make('image_path')->maxLength(255),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_published')->default(true),
            Forms\Components\Toggle::make('show_prices')->default(true),
            Forms\Components\Toggle::make('show_availability')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->toggleable(),
                Tables\Columns\TextColumn::make('external_id')->label('1C')->toggleable(),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Опубликовано'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Опубликовать')
                        ->action(fn ($records) => $records->each->update(['is_published' => true])),
                    Tables\Actions\BulkAction::make('hide')
                        ->label('Скрыть')
                        ->action(fn ($records) => $records->each->update(['is_published' => false])),
                    Tables\Actions\BulkAction::make('show_prices')
                        ->label('Показать цены')
                        ->action(fn ($records) => $records->each->update(['show_prices' => true])),
                    Tables\Actions\BulkAction::make('hide_prices')
                        ->label('Скрыть цены')
                        ->action(fn ($records) => $records->each->update(['show_prices' => false])),
                    Tables\Actions\BulkAction::make('show_availability')
                        ->label('Показать наличие')
                        ->action(fn ($records) => $records->each->update(['show_availability' => true])),
                    Tables\Actions\BulkAction::make('hide_availability')
                        ->label('Скрыть наличие')
                        ->action(fn ($records) => $records->each->update(['show_availability' => false])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
