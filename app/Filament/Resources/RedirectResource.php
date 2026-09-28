<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages;
use App\Models\Redirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'SEO';

    protected static ?string $modelLabel = 'Редирект';

    protected static ?string $pluralModelLabel = 'Редиректы';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('from_path')->required()->maxLength(255)->helperText('Например /old-page.html'),
            Forms\Components\TextInput::make('to_path')->required()->maxLength(255),
            Forms\Components\TextInput::make('status_code')->numeric()->default(301),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_path')->searchable(),
                Tables\Columns\TextColumn::make('to_path')->searchable(),
                Tables\Columns\TextColumn::make('status_code'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
