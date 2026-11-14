<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Support\DocumentGuesser;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/**
 * Add a document in three steps: Upload, Details, Publish.
 * Nothing is saved until the last step, and nothing is public unless the user picks Publish now or Schedule.
 */
class AddDocument extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static ?string $navigationLabel = 'Add a document';

    protected static string|\UnitEnum|null $navigationGroup = 'Investors Corner';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'add-document';

    protected string $view = 'filament.pages.add-document';

    public ?array $data = [];

    /** Set after the last step: what was created and what was skipped. */
    public ?array $result = null;

    public function getTitle(): string
    {
        return 'Add a document';
    }

    public static function canAccess(): bool
    {
        return DocumentResource::canViewAny();
    }

    private static function canPublish(): bool
    {
        return auth()->user()?->hasAnyRole([User::ADMIN, User::EDITOR]) ?? false;
    }

    public function mount(): void
    {
        $this->startOver();
    }

    public function startOver(): void
    {
        $this->result = null;
        $this->form->fill(['when' => static::canPublish() ? 'now' : 'draft', 'docs' => []]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Wizard::make([
                Step::make('Upload')->description('Choose the files')->schema([
                    FileUpload::make('files')->label('Files')->multiple()->required()->minFiles(1)->maxFiles(20)
                        ->disk('investor')->directory(fn () => 'uploads/'.now()->format('Y'))->preserveFilenames()
                        ->acceptedFileTypes(array_keys(Document::MIMES))->maxSize(Document::MAX_KB)
                        ->helperText('One file or several. PDF, or Excel or Word for older items. Up to 25 MB each. The file type is checked from the file itself.'),
                ])->afterValidation(fn () => $this->prepareDetails()),

                Step::make('Details')->description('Section, year, title')->schema([
                    Repeater::make('docs')->label(false)->addable(false)->deletable(false)->reorderable(false)->schema([
                        Hidden::make('path'),
                        Text::make(fn ($get) => 'File: '.basename((string) $get('path'))),
                        TextInput::make('title')->required()->maxLength(200)->helperText('What investors will see.'),
                        Select::make('category_id')->label('Section')->required()->searchable()->live()->options(fn () => $this->categoryOptions()),
                        Select::make('fiscal_year')->label('Financial year')->options(fn () => DocumentResource::fiscalYears())->searchable()
                            ->required(fn ($get) => (bool) Category::find($get('category_id'))?->group_by_year)
                            ->helperText('Needed for sections that group documents by year.'),
                        TextInput::make('period')->maxLength(60)->helperText('Optional. For example Q2, H1, or 30 Sep 2025.'),
                        DatePicker::make('doc_date')->label('Date on the document'),
                    ])->columns(2),
                ]),

                Step::make('Publish')->description('Now, later, or as draft')->schema([
                    Text::make(fn () => new HtmlString(
                        '<ul>'.collect($this->data['docs'] ?? [])->map(fn ($d) => '<li><b>'.e($d['title'] ?? '').'</b></li>')->implode('').'</ul>'
                    )),
                    Radio::make('when')->label('When should it go live?')->live()->required()
                        ->options(static::canPublish()
                            ? ['now' => 'Publish now', 'schedule' => 'Schedule', 'draft' => 'Save as draft']
                            : ['draft' => 'Save as draft'])
                        ->descriptions([
                            'now' => 'Visible on the website straight away.',
                            'schedule' => 'Goes live by itself at the time you choose (India time).',
                            'draft' => 'Not public. Finish later from Documents.',
                        ])
                        ->helperText(static::canPublish() ? null : 'Reviewers save drafts. An Editor publishes.'),
                    DateTimePicker::make('publish_at')->label('Go live at')->seconds(false)->after('now')
                        ->visible(fn ($get) => $get('when') === 'schedule')->required(fn ($get) => $get('when') === 'schedule'),
                ]),
            ])->submitAction(new HtmlString(Blade::render(
                // The label follows the chosen option in the browser, so it never lags behind the radio buttons.
                '<x-filament::button type="submit"><span x-text="({now: \'Publish now\', schedule: \'Schedule\', draft: \'Save draft\'})[$wire.data.when] ?? \'Finish\'">Finish</span></x-filament::button>'
            ))),
        ]);
    }

    private function categoryOptions(): array
    {
        return Category::with('parent')->orderBy('sort_order')->get()
            ->mapWithKeys(fn ($c) => [$c->id => ($c->parent ? $c->parent->name.' › ' : '').$c->name])->all();
    }

    /** Step 1 to 2: store the files and fill the details from the file names, keeping anything already edited. */
    private function prepareDetails(): void
    {
        $this->form->getComponentByStatePath('data.files', withAbsoluteStatePath: true)?->saveUploadedFiles();
        $paths = array_values(array_filter((array) ($this->data['files'] ?? []), 'is_string'));
        $existing = collect($this->data['docs'] ?? [])->keyBy('path');
        $categories = Category::all();
        $docs = [];
        foreach ($paths as $path) {
            $name = basename($path);
            $docs[(string) \Illuminate\Support\Str::uuid()] = $existing[$path] ?? [
                'path' => $path,
                'title' => DocumentGuesser::title($name),
                'category_id' => DocumentGuesser::category($name, $categories)?->id,
                'fiscal_year' => DocumentGuesser::fiscalYear($name),
                'period' => null,
                'doc_date' => null,
            ];
        }
        $this->data['docs'] = $docs;
    }

    public function submit(): void
    {
        $state = $this->form->getState();
        $when = static::canPublish() ? $state['when'] : 'draft';
        $disk = Storage::disk('investor');
        $created = [];
        $skipped = [];

        foreach ($state['docs'] as $d) {
            $existing = Document::where('checksum', Document::checksum($disk, $d['path']))->first();
            if ($existing) {
                $disk->delete($d['path']);
                $skipped[] = ['name' => basename($d['path']), 'title' => $existing->title, 'id' => $existing->id];

                continue;
            }
            $doc = Document::create([
                'title' => $d['title'], 'category_id' => $d['category_id'], 'fiscal_year' => $d['fiscal_year'] ?? null,
                'period' => $d['period'] ?? null, 'doc_date' => $d['doc_date'] ?? null, 'file_path' => $d['path'],
                'original_name' => basename($d['path']),
                'status' => ['now' => Document::PUBLISHED, 'schedule' => Document::SCHEDULED][$when] ?? Document::DRAFT,
                'publish_at' => $when === 'schedule' ? $state['publish_at'] : null,
            ]);
            $created[] = ['id' => $doc->id, 'title' => $doc->title, 'url' => $doc->url()];
        }

        $this->result = ['when' => $when, 'created' => $created, 'skipped' => $skipped];
    }
}
