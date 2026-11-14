<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Staff accounts. Admin only. Accounts are deactivated, never deleted, so the activity log keeps its names. */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Staff accounts';

    protected static ?string $modelLabel = 'staff account';

    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->label('Email address')->email()->required()->unique(ignoreRecord: true),
            Select::make('roles')
                ->relationship('roles', 'name')
                ->preload()
                ->required()
                ->helperText('Admin manages everything. Editor publishes documents. Reviewer prepares drafts but cannot publish.'),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->minLength(12)
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText('At least 12 characters. Leave blank to keep the current password.'),
            Toggle::make('is_active')->label('Can sign in')->default(true)
                ->helperText('Turn off when someone leaves. Their past activity stays in the log.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->label('Email address')->searchable(),
                TextColumn::make('roles.name')->badge(),
                IconColumn::make('app_authentication_secret')->label('2-step set up')
                    ->boolean()->getStateUsing(fn (User $record) => filled($record->app_authentication_secret)),
                IconColumn::make('is_active')->label('Can sign in')->boolean(),
                TextColumn::make('last_login_at')->label('Last signed in')->dateTime('d M Y, H:i')->placeholder('Never'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
