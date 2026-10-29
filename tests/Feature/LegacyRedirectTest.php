<?php

namespace Tests\Feature;

use App\Models\Document;
use Database\Seeders\InvestorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InvestorSeeder::class);
    }

    public function test_old_section_pages_go_to_the_new_sections(): void
    {
        $this->get('/reports/9/financial-results/2022-23')->assertStatus(301)->assertRedirect('/investors/financial-results?fy=2022-23');
        $this->get('/reports/17/notices-corporate-announcements/intimation')->assertStatus(301)->assertRedirect('/investors/announcements/intimation');
        $this->get('/reports/1/annual-reports')->assertStatus(301)->assertRedirect('/investors/annual-reports');
    }

    public function test_every_old_section_id_lands_on_a_working_page(): void
    {
        foreach (array_keys(config('legacy.reports')) as $id) {
            $loc = $this->get("/reports/$id/x")->assertStatus(301)->headers->get('Location');
            $this->get($loc)->assertOk();
        }
    }

    public function test_old_file_addresses_redirect_to_the_document_address(): void
    {
        $d = Document::where('title', 'PG FOILS AR - 2024-25')->firstOrFail();
        $this->get('/storage/reports/annual-reports/PG%20FOILS%20AR%20-%202024-25.pdf.pdf')->assertStatus(301)->assertRedirect($d->url());
    }

    public function test_every_migrated_document_keeps_a_working_old_address(): void
    {
        $withPath = Document::whereNotNull('legacy_path')->get();
        $this->assertSame(Document::count(), $withPath->count());

        $bad = [];
        foreach ($withPath as $d) {
            $res = $this->get(implode('/', array_map('rawurlencode', explode('/', $d->legacy_path))));
            if ($res->status() !== 301) {
                $bad[] = $d->legacy_path;
            }
        }
        $this->assertSame([], $bad);
    }

    public function test_unpublished_documents_no_longer_redirect(): void
    {
        $d = Document::where('title', 'PG FOILS AR - 2024-25')->firstOrFail();
        $d->updateQuietly(['status' => Document::UNPUBLISHED]);
        $this->get('/storage/reports/annual-reports/PG%20FOILS%20AR%20-%202024-25.pdf.pdf')->assertNotFound();
    }

    public function test_fixed_files_old_pages_and_unknown_paths(): void
    {
        $this->get('/storage/products/product-specifications.pdf')->assertStatus(301)->assertRedirect('/docs/product-specifications.pdf');
        $this->get('/storage/csr/01.%20CSR%20COMMITTEE%20STRUCTURE.pdf')->assertStatus(301)->assertRedirect('/docs/csr/01.%20CSR%20COMMITTEE%20STRUCTURE.pdf');
        $this->get('/products.html')->assertStatus(301)->assertRedirect('/products');
        $this->get('/grivances-management.html')->assertStatus(301)->assertRedirect('/grievance');
        $this->get('/storage/nothing/here.pdf')->assertNotFound();
        $this->get('/reports/999/x')->assertNotFound();
    }

    public function test_the_csr_and_specification_files_exist_where_redirects_point(): void
    {
        $this->assertFileExists(public_path('docs/product-specifications.pdf'));
        foreach (config('content.csr_docs') as [, $file]) {
            $this->assertFileExists(public_path('docs/csr/'.$file));
        }
    }
}
