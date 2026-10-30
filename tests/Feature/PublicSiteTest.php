<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_with_core_content_and_seo(): void
    {
        $res = $this->get('/');

        $res->assertOk()
            ->assertSee('third&#8209;largest', false)
            ->assertSee('<title>PG Foils Ltd</title>', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('Skip to content');
    }

    public function test_every_placeholder_route_responds(): void
    {
        foreach (['products', 'company', 'plants', 'clients', 'certificates', 'sustainability', 'investors', 'grievance', 'contact', 'quote'] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_pages_set_a_unique_title(): void
    {
        $this->get(route('products'))->assertSee('<title>Products | PG Foils Ltd</title>', false);
    }

    public function test_health_endpoint_is_up(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/does-not-exist')->assertNotFound();
    }
}
