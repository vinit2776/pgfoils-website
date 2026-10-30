<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
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
use Illuminate\Support\Str;

/** The Investors Corner menu. Admin and Editor manage it. Sections are hidden, never deleted. */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = 'Investors menu';

    protected static ?string $modelLabel = 'section';

    protected static string|\UnitEnum|null $navigationGroup = 'Investors Corner';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([User::ADMIN, User::EDITOR]) ?? false;
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
            TextInput::make('name')->required()->maxLength(120)->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $get) => $get('slug') ?: $set('slug', Str::slug($state))),
            TextInput::make('slug')->label('Web address ending')->required()->maxLength(80)->alphaDash()
                ->helperText('Used in the page address, for example /investors/annual-reports. Changing it changes the address.'),
            Select::make('parent_id')->label('Inside section')->placeholder('Top level')
                ->options(fn () => Category::topLevel()->pluck('name', 'id'))
                ->helperText('Choose a section to make this a sub-section. Only two levels are allowed.'),
            TextInput::make('sort_order')->label('Order')->numeric()->default(0)->helperText('Smaller numbers come first.'),
            Toggle::make('group_by_year')->label('Group documents by financial year')->default(false),
            Toggle::make('visible')->label('Show on the website')->default(true)
                ->helperText('Turn off to hide the section. Its documents are kept.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->formatStateUsing(fn (Category $r, $state) => ($r->parent_id ? '— ' : '').$state)->searchable(),
                TextColumn::make('parent.name')->label('Inside')->placeholder('Top level'),
                TextColumn::make('documents_count')->label('Documents')->counts('documents'),
                IconColumn::make('group_by_year')->label('By year')->boolean(),
                IconColumn::make('visible')->label('Shown')->boolean(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}
