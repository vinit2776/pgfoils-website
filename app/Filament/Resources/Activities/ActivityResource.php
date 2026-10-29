<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ManageActivities;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

/** Read-only record of who did what. Nothing here can be edited or deleted from the admin. */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $modelLabel = 'activity';

    protected static ?int $navigationSort = 99;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, H:i:s')->sortable(),
                TextColumn::make('causer.name')->label('Who')->placeholder('System / not signed in')->searchable(),
                TextColumn::make('description')->label('What')->searchable(),
                TextColumn::make('log_name')->label('Area')->badge(),
                TextColumn::make('properties.ip')->label('IP address')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Area')->options(fn () => Activity::query()->distinct()->pluck('log_name', 'log_name')->all()),
            ])
            ->recordActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageActivities::route('/')];
    }
}
