<?php

namespace Tests\Feature;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\InvestorSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvestorsCornerTest extends TestCase
{
    use RefreshDatabase;

    private function doc(array $attrs = []): Document
    {
        $cat = Category::firstOrCreate(['parent_id' => null, 'slug' => 'annual-reports'], ['name' => 'Annual reports']);

        return Document::create($attrs + ['category_id' => $cat->id, 'title' => 'Annual report 2025-26', 'status' => Document::PUBLISHED,
            'external_url' => 'https://www.pgfoils.co.in/storage/reports/a b.pdf', 'file_type' => 'pdf']);
    }

    private function staff(string $role): User
    {
        $this->seed(RoleSeeder::class);
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole($role);
        $u->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        return $u;
    }

    public function test_the_current_sites_documents_are_all_seeded_and_published(): void
    {
        $this->seed(InvestorSeeder::class);
        $this->assertSame(177, Document::count());
        $this->assertSame(177, Document::live()->count());
        $this->get('/investors')->assertOk()->assertSee('Annual reports')->assertSee('Financial results');
    }

    public function test_only_live_documents_show_publicly(): void
    {
        $this->doc(['title' => 'Visible one']);
        $this->doc(['title' => 'Draft one', 'status' => Document::DRAFT]);
        $this->doc(['title' => 'Hidden one', 'status' => Document::UNPUBLISHED]);
        $this->doc(['title' => 'Later one', 'status' => Document::SCHEDULED, 'publish_at' => now()->addDay()]);
        $this->doc(['title' => 'Due one', 'status' => Document::SCHEDULED, 'publish_at' => now()->subMinute()]);

        $this->get('/investors')->assertSee('Visible one')->assertSee('Due one')
            ->assertDontSee('Draft one')->assertDontSee('Hidden one')->assertDontSee('Later one');
    }

    public function test_search_and_year_filters_work(): void
    {
        $this->doc(['title' => 'Alpha results', 'fiscal_year' => '2024-25']);
        $this->doc(['title' => 'Beta results', 'fiscal_year' => '2025-26']);

        $this->get('/investors?q=alpha')->assertSee('Alpha results')->assertDontSee('Beta results');
        $this->get('/investors?fy=2025-26')->assertSee('Beta results')->assertDontSee('Alpha results');
    }

    public function test_hidden_sections_are_not_reachable(): void
    {
        $this->doc();
        Category::where('slug', 'annual-reports')->update(['visible' => false]);
        $this->get('/investors/annual-reports')->assertNotFound();
        $this->get('/investors')->assertDontSee('Annual report 2025-26');
    }

    public function test_download_address_serves_files_and_hides_drafts(): void
    {
        Storage::fake('investor');
        Storage::disk('investor')->put('uploads/2026/r.pdf', "%PDF-1.4\nfake");
        $d = $this->doc(['external_url' => null, 'file_path' => 'uploads/2026/r.pdf']);

        $res = $this->get($d->url())->assertOk();
        $this->assertStringContainsString('inline', $res->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        $this->assertNotNull($d->fresh()->checksum);

        $d->update(['status' => Document::DRAFT]);
        $this->get($d->url())->assertNotFound();
    }

    public function test_legacy_documents_redirect_to_the_current_site_with_a_safe_address(): void
    {
        $d = $this->doc();
        $this->get($d->url())->assertRedirect('https://www.pgfoils.co.in/storage/reports/a%20b.pdf');
    }

    public function test_replacing_a_file_keeps_the_old_one_and_the_same_address(): void
    {
        Storage::fake('investor');
        Storage::disk('investor')->put('uploads/2026/old.pdf', 'old');
        Storage::disk('investor')->put('uploads/2026/new.pdf', 'new!');
        $d = $this->doc(['external_url' => null, 'file_path' => 'uploads/2026/old.pdf']);
        $url = $d->url();
        $oldSum = $d->fresh()->checksum;

        $d->replaceFile('uploads/2026/new.pdf');

        $this->assertSame($url, $d->fresh()->url());
        $this->assertSame('uploads/2026/new.pdf', $d->fresh()->file_path);
        $this->assertNotSame($oldSum, $d->fresh()->checksum);
        $this->assertDatabaseHas('document_versions', ['document_id' => $d->id, 'file_path' => 'uploads/2026/old.pdf']);
        Storage::disk('investor')->assertExists('uploads/2026/old.pdf');
        $this->assertDatabaseHas('activity_log', ['description' => 'File replaced']);
    }

    public function test_an_exact_copy_of_a_stored_file_is_refused(): void
    {
        Storage::fake('investor');
        Storage::disk('investor')->put('uploads/2026/a.pdf', 'same bytes');
        Storage::disk('investor')->put('uploads/2026/b.pdf', 'same bytes');
        $this->doc(['external_url' => null, 'file_path' => 'uploads/2026/a.pdf']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->doc(['title' => 'Copy', 'external_url' => null, 'file_path' => 'uploads/2026/b.pdf']);
    }

    public function test_a_duplicate_is_removed_from_storage_and_not_saved(): void
    {
        Storage::fake('investor');
        Storage::disk('investor')->put('uploads/2026/a.pdf', 'same bytes');
        Storage::disk('investor')->put('uploads/2026/b.pdf', 'same bytes');
        $this->doc(['external_url' => null, 'file_path' => 'uploads/2026/a.pdf']);

        try {
            $this->doc(['title' => 'Copy', 'external_url' => null, 'file_path' => 'uploads/2026/b.pdf']);
        } catch (\Illuminate\Validation\ValidationException) {
        }

        $this->assertDatabaseMissing('documents', ['title' => 'Copy']);
        Storage::disk('investor')->assertMissing('uploads/2026/b.pdf');
        Storage::disk('investor')->assertExists('uploads/2026/a.pdf');
    }

    public function test_a_reviewer_cannot_publish_but_an_editor_can(): void
    {
        $d = $this->doc(['status' => Document::DRAFT]);

        $this->actingAs($this->staff(User::REVIEWER));
        try {
            $d->update(['status' => Document::PUBLISHED]);
            $this->fail('Reviewer published a document.');
        } catch (\DomainException) {
            $this->assertSame(Document::DRAFT, $d->fresh()->status);
        }

        $this->actingAs($this->staff(User::EDITOR));
        $d->update(['status' => Document::PUBLISHED]);
        $this->assertSame(Document::PUBLISHED, $d->fresh()->status);
        $this->assertNotNull($d->fresh()->published_at);
    }

    public function test_a_reviewer_can_only_edit_drafts_and_nobody_can_delete(): void
    {
        $draft = $this->doc(['status' => Document::DRAFT]);
        $live = $this->doc(['title' => 'Live one']);

        $this->actingAs($this->staff(User::REVIEWER));
        $this->assertTrue(DocumentResource::canEdit($draft));
        $this->assertFalse(DocumentResource::canEdit($live));
        $this->assertFalse(DocumentResource::canDelete($draft));

        $this->actingAs($this->staff(User::ADMIN));
        $this->assertFalse(DocumentResource::canDelete($live));
        $this->assertFalse(DocumentResource::canDeleteAny());
    }

    public function test_admin_screens_follow_the_roles(): void
    {
        $this->actingAs($e = $this->staff(User::EDITOR));
        $this->get('/admin/documents')->assertOk();
        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/users')->assertForbidden();

        $r = $this->staff(User::REVIEWER);
        $this->actingAs($r)->get('/admin/documents')->assertOk();
        $this->actingAs($r)->get('/admin/categories')->assertForbidden();
    }

    public function test_the_sitemap_lists_visible_sections(): void
    {
        $this->doc();
        $this->get('/sitemap.xml')->assertOk()->assertSee('/investors/annual-reports', false);
    }
}
