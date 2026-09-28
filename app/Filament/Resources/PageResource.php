<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Контент';

    protected static ?string $modelLabel = 'Страница';

    protected static ?string $pluralModelLabel = 'Страницы';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Заголовок')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')
                ->label('Slug (URL без .html)')
                ->required()
                ->maxLength(255)
                ->helperText('Примеры: about, contacts, service/montazhnye-raboty. При смене slug старый URL получит 301.'),
            Forms\Components\RichEditor::make('body_html')->label('Содержимое')->columnSpanFull(),
            Forms\Components\TextInput::make('seo_title')->label('SEO title')->maxLength(255),
            Forms\Components\Textarea::make('seo_description')->label('SEO description')->rows(3),
            Forms\Components\Toggle::make('is_published')->label('Опубликовано')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->defaultSort('slug')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Заголовок')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Опубл.')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Изменено')->dateTime('d.m.Y H:i')->sortable(),
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
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
