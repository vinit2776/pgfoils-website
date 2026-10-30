<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\ManageDocuments;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Investors Corner documents. Editor and Admin: everything. Reviewer: add documents and edit drafts, cannot publish.
 * Nobody deletes: unpublish hides a document and replace keeps the old file as an archived version.
 */
class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Documents';

    protected static string|\UnitEnum|null $navigationGroup = 'Investors Corner';

    protected static ?int $navigationSort = 10;

    private static function canPublish(): bool
    {
        return auth()->user()?->hasAnyRole([User::ADMIN, User::EDITOR]) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([User::ADMIN, User::EDITOR, User::REVIEWER]) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canPublish() || ($record->status === Document::DRAFT && static::canViewAny());
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /** Financial years from FY 2014-15 to next year, newest first. */
    public static function fiscalYears(): array
    {
        $years = [];
        for ($y = (int) now()->format('Y') + 1; $y >= 2014; $y--) {
            $label = $y.'-'.substr((string) ($y + 1), 2);
            $years[$label] = $label;
        }

        return $years;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category_id')->label('Section')->required()->searchable()
                ->options(fn () => Category::with('parent')->orderBy('sort_order')->get()->mapWithKeys(fn ($c) => [$c->id => ($c->parent ? $c->parent->name.' › ' : '').$c->name])),
            TextInput::make('title')->required()->maxLength(200)
                ->helperText('The name shown to investors, for example "Unaudited financial results for the quarter ended 30 Sep 2025".'),
            Select::make('fiscal_year')->label('Financial year')->options(fn () => static::fiscalYears())->searchable()
                ->helperText('Needed for sections that group documents by year.'),
            TextInput::make('period')->maxLength(60)->helperText('Optional. For example Q2, H1, or 30 Sep 2025.'),
            DatePicker::make('doc_date')->label('Date on the document'),
            FileUpload::make('file_path')->label('File')
                ->disk('investor')->directory(fn () => 'uploads/'.now()->format('Y'))->preserveFilenames()
                ->acceptedFileTypes(array_keys(Document::MIMES))->maxSize(Document::MAX_KB)
                ->helperText('PDF, or Excel or Word for older items. Up to 25 MB. The file type is checked from the file itself.'),
            Select::make('status')->options(fn () => static::canPublish()
                ? Document::STATUSES
                : [Document::DRAFT => Document::STATUSES[Document::DRAFT]])
                ->default(Document::DRAFT)->required()->live()
                ->helperText(fn () => static::canPublish() ? 'Draft is hidden. Published shows on the website now. Scheduled shows at the time below. Unpublished hides it again.' : 'Reviewers save drafts. An Editor publishes.'),
            DateTimePicker::make('publish_at')->label('Publish at')->seconds(false)
                ->visible(fn ($get) => $get('status') === Document::SCHEDULED)
                ->required(fn ($get) => $get('status') === Document::SCHEDULED)->after('now')
                ->helperText('India time.'),
            DatePicker::make('last_updated_on_site')->label('Last updated on website')
                ->helperText('Shown to investors for policy-type items. Set automatically when the file is replaced.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->wrap()->limit(80),
                TextColumn::make('category.name')->label('Section')->sortable(),
                TextColumn::make('fiscal_year')->label('Year')->sortable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => Document::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'published' => 'success', 'scheduled' => 'warning', 'unpublished' => 'gray', default => 'info' }),
                TextColumn::make('published_at')->label('Published')->dateTime('d M Y, H:i')->placeholder('-'),
                TextColumn::make('file_type')->label('Type')->badge()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Section')->options(fn () => Category::orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('fiscal_year')->label('Year')->options(fn () => static::fiscalYears()),
                SelectFilter::make('status')->options(Document::STATUSES),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('replace')->label('Replace file')->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Document $r) => static::canPublish())
                    ->modalDescription('The current file is kept as an archived version. The web address of the document stays the same.')
                    ->schema([
                        FileUpload::make('file')->label('New file')->required()->disk('investor')->directory(fn () => 'uploads/'.now()->format('Y'))
                            ->preserveFilenames()->acceptedFileTypes(array_keys(Document::MIMES))->maxSize(Document::MAX_KB),
                    ])
                    ->action(function (Document $record, array $data) {
                        $record->replaceFile($data['file']);
                        Notification::make()->title('File replaced')->success()->send();
                    }),
                Action::make('publish')->label(fn (Document $r) => $r->status === Document::UNPUBLISHED ? 'Publish again' : 'Publish')
                    ->icon(Heroicon::OutlinedRocketLaunch)->color('success')->requiresConfirmation()
                    ->modalHeading('Publish this document?')
                    ->modalDescription('It becomes visible on the website straight away.')
                    ->visible(fn (Document $r) => static::canPublish() && in_array($r->status, [Document::DRAFT, Document::UNPUBLISHED], true))
                    ->action(function (Document $record) {
                        $record->update(['status' => Document::PUBLISHED, 'publish_at' => null]);
                        Notification::make()->title('Published')->success()->send();
                    }),
                Action::make('schedule')->label('Schedule')->icon(Heroicon::OutlinedClock)->color('gray')
                    ->modalDescription('The document goes live by itself at this time (India time).')
                    ->visible(fn (Document $r) => static::canPublish() && $r->status === Document::DRAFT)
                    ->schema([DateTimePicker::make('publish_at')->label('Go live at')->seconds(false)->required()->after('now')])
                    ->action(function (Document $record, array $data) {
                        $record->update(['status' => Document::SCHEDULED, 'publish_at' => $data['publish_at']]);
                        Notification::make()->title('Scheduled')->success()->send();
                    }),
                Action::make('unpublish')->label('Unpublish')->icon(Heroicon::OutlinedEyeSlash)->color('gray')->requiresConfirmation()
                    ->modalDescription('The document is hidden from the website. Nothing is deleted and you can publish it again.')
                    ->visible(fn (Document $r) => static::canPublish() && $r->isLive())
                    ->action(function (Document $record) {
                        $record->update(['status' => Document::UNPUBLISHED]);
                        Notification::make()->title('Unpublished')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDocuments::route('/')];
    }
}
