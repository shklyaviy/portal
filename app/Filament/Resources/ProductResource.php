<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('external_id')->label('1C ID')->maxLength(255),
            Forms\Components\TextInput::make('sku')->label('Артикул')->maxLength(255),
            Forms\Components\TextInput::make('name')->required()->maxLength(255)
                ->helperText('Используется как alt для изображения'),
            Forms\Components\Toggle::make('name_locked')->label('Имя защищено от 1С'),
            Forms\Components\TextInput::make('slug')->required()->maxLength(255),
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('price')->numeric(),
            Forms\Components\TextInput::make('currency')->default('RUB')->maxLength(8),
            Forms\Components\TextInput::make('unit')->maxLength(255),
            Forms\Components\RichEditor::make('description_html')->label('Описание')->columnSpanFull(),
            Forms\Components\TextInput::make('seo_title')->maxLength(255),
            Forms\Components\Textarea::make('seo_description')->rows(3),
            Forms\Components\TextInput::make('seo_h1')->maxLength(255),
            Forms\Components\FileUpload::make('image_path')
                ->label('Изображение')
                ->disk('public')
                ->directory('products')
                ->image()
                ->maxSize(10240)
                ->helperText('Сохраняется в storage/app/public/products. Alt на витрине = название товара. Max 1600px + thumb 400px.'),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_published')->default(true),
            Forms\Components\Toggle::make('show_price')->default(true),
            Forms\Components\Toggle::make('show_availability')->default(true),
            Forms\Components\Toggle::make('is_in_stock')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100])
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sku')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('external_id')->label('1C')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('category.name')->label('Категория')->toggleable(),
                Tables\Columns\TextColumn::make('price')->money('RUB')->sortable(),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\IconColumn::make('show_price')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Опубликовано'),
                Tables\Filters\TernaryFilter::make('show_price')->label('Показывать цену'),
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
                    Tables\Actions\BulkAction::make('show_price')
                        ->label('Показать цену')
                        ->action(fn ($records) => $records->each->update(['show_price' => true])),
                    Tables\Actions\BulkAction::make('hide_price')
                        ->label('Скрыть цену')
                        ->action(fn ($records) => $records->each->update(['show_price' => false])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
