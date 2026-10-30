<?php

namespace Tests\Feature;

use App\Filament\Pages\AddDocument;
use App\Filament\Resources\Documents\Pages\ManageDocuments;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Support\DocumentGuesser;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PublishingWizardTest extends TestCase
{
    use RefreshDatabase;

    private Category $results;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('investor');
        $this->results = Category::create(['name' => 'Financial results', 'slug' => 'financial-results', 'group_by_year' => true]);
    }

    private function as(string $role): void
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole($role);
        $this->actingAs($u);
    }

    private function pdf(string $name, string $body = 'a'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".$body);
    }

    public function test_file_names_are_turned_into_title_year_and_section(): void
    {
        $this->assertSame('Q2 results sep 2025', DocumentGuesser::title('Q2-results-sep-2025.pdf'));
        $this->assertSame('2025-26', DocumentGuesser::fiscalYear('Q2-results-sep-2025.pdf'));
        $this->assertSame('2024-25', DocumentGuesser::fiscalYear('results-feb-2025.pdf'));
        $this->assertSame('2025-26', DocumentGuesser::fiscalYear('annual-report-2025-26.pdf'));
        $this->assertNull(DocumentGuesser::fiscalYear('scan_0042.pdf'));
        $cats = collect([$this->results, Category::create(['name' => 'Annual reports', 'slug' => 'annual-reports'])]);
        $this->assertSame($this->results->id, DocumentGuesser::category('Q2-results-sep-2025.pdf', $cats)->id);
        $this->assertNull(DocumentGuesser::category('scan_0042.pdf', $cats));
    }

    public function test_an_editor_uploads_checks_details_and_publishes_in_one_flow(): void
    {
        $this->as(User::EDITOR);

        $page = Livewire::test(AddDocument::class)
            ->fillForm(['files' => [$this->pdf('Q2-results-sep-2025.pdf')]])
            ->goToNextWizardStep();

        $docs = array_values($page->get('data.docs'));
        $this->assertCount(1, $docs);
        $this->assertSame('Q2 results sep 2025', $docs[0]['title']);
        $this->assertSame($this->results->id, $docs[0]['category_id']);
        $this->assertSame('2025-26', $docs[0]['fiscal_year']);

        $page->goToNextWizardStep()->fillForm(['when' => 'now'])->call('submit')->assertHasNoFormErrors();

        $doc = Document::firstWhere('title', 'Q2 results sep 2025');
        $this->assertSame(Document::PUBLISHED, $doc->status);
        $this->assertTrue($doc->isLive());
        $this->get($doc->url())->assertOk();
    }

    public function test_the_details_step_blocks_a_missing_section(): void
    {
        $this->as(User::EDITOR);

        $page = Livewire::test(AddDocument::class)
            ->fillForm(['files' => [$this->pdf('scan_0042.pdf')]])
            ->goToNextWizardStep();
        $key = array_key_first($page->get('data.docs'));
        $page->goToNextWizardStep()->assertHasFormErrors(["docs.$key.category_id" => 'required']);
        $this->assertSame(0, Document::count());
    }

    public function test_save_as_draft_keeps_it_off_the_website(): void
    {
        $this->as(User::EDITOR);

        Livewire::test(AddDocument::class)
            ->fillForm(['files' => [$this->pdf('Q2-results-sep-2025.pdf')]])
            ->goToNextWizardStep()->goToNextWizardStep()
            ->fillForm(['when' => 'draft'])->call('submit');

        $doc = Document::firstOrFail();
        $this->assertSame(Document::DRAFT, $doc->status);
        $this->get($doc->url())->assertNotFound();
    }

    public function test_a_reviewer_can_only_save_a_draft(): void
    {
        $this->as(User::REVIEWER);

        $page = Livewire::test(AddDocument::class)
            ->fillForm(['files' => [$this->pdf('Q2-results-sep-2025.pdf')]])
            ->goToNextWizardStep()->goToNextWizardStep();

        $page->fillForm(['when' => 'now'])->call('submit')->assertHasFormErrors(['when']);
        $this->assertSame(0, Document::count());

        $page->fillForm(['when' => 'draft'])->call('submit');
        $this->assertSame(Document::DRAFT, Document::firstOrFail()->status);
    }

    public function test_an_exact_duplicate_is_skipped_and_reported(): void
    {
        $this->as(User::EDITOR);
        Storage::disk('investor')->put('uploads/2026/old.pdf', "%PDF-1.4\nsame");
        Document::create(['category_id' => $this->results->id, 'title' => 'Old', 'status' => Document::PUBLISHED, 'file_path' => 'uploads/2026/old.pdf']);

        $page = Livewire::test(AddDocument::class)
            ->fillForm(['files' => [$this->pdf('Q2-results-sep-2025.pdf', 'same')]])
            ->goToNextWizardStep()->goToNextWizardStep()
            ->fillForm(['when' => 'draft'])->call('submit');

        $this->assertSame(1, Document::count());
        $this->assertSame('Old', $page->get('result.skipped.0.title'));
    }

    public function test_a_draft_can_be_published_from_the_documents_list(): void
    {
        $this->as(User::EDITOR);
        $doc = Document::create(['category_id' => $this->results->id, 'title' => 'Draft', 'status' => Document::DRAFT, 'external_url' => 'https://example.com/a.pdf']);

        Livewire::test(ManageDocuments::class)->callTableAction('publish', $doc);

        $this->assertSame(Document::PUBLISHED, $doc->fresh()->status);
        $this->assertNotNull($doc->fresh()->published_at);
    }

    public function test_a_reviewer_does_not_get_the_publish_action(): void
    {
        $this->as(User::REVIEWER);
        $doc = Document::create(['category_id' => $this->results->id, 'title' => 'Draft', 'status' => Document::DRAFT, 'external_url' => 'https://example.com/a.pdf']);

        Livewire::test(ManageDocuments::class)->assertTableActionHidden('publish', $doc);
    }
}
