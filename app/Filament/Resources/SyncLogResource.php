<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SyncLogResource\Pages;
use App\Models\SyncLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $modelLabel = 'Лог синка';

    protected static ?string $pluralModelLabel = 'Логи синка';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('source')->disabled(),
            Forms\Components\TextInput::make('mode')->disabled(),
            Forms\Components\TextInput::make('batch_id')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\Textarea::make('message')->disabled()->columnSpanFull(),
            Forms\Components\Textarea::make('stats')
                ->formatStateUsing(fn ($state) => is_array($state)
                    ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    : (string) $state)
                ->disabled()
                ->rows(8)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('payload_hash')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('mode'),
                Tables\Columns\TextColumn::make('batch_id')->toggleable(),
                Tables\Columns\TextColumn::make('message')->limit(60),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncLogs::route('/'),
            'view' => Pages\ViewSyncLog::route('/{record}'),
        ];
    }
}
