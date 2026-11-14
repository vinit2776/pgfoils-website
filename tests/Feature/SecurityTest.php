<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityTest extends TestCase
{
    public function test_security_headers_are_sent(): void
    {
        $res = $this->get('/');

        $res->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_strict_csp_applies_to_public_pages_but_not_admin(): void
    {
        $this->app['env'] = 'staging'; // CSP is skipped in local so Vite's dev server works

        $this->get('/')->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $this->get('/')->headers->get('Content-Security-Policy'));
        $this->assertNull($this->get('/admin/login')->headers->get('Content-Security-Policy'));
    }

    public function test_public_pages_ship_no_inline_scripts_or_styles(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/i', $html, 'inline <script> found');
        $this->assertDoesNotMatchRegularExpression('/\sstyle="/i', $html, 'inline style attribute found');
    }

    public function test_staging_gate_blocks_without_password_and_allows_with_it(): void
    {
        $this->app['env'] = 'staging';
        config(['site.staging_password' => 's3cret-test']);

        $this->get('/')->assertStatus(401)->assertHeader('WWW-Authenticate');
        $this->get('/up')->assertOk(); // health check stays open

        $this->get('/', ['Authorization' => 'Basic '.base64_encode('anyone:s3cret-test')])
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/', ['Authorization' => 'Basic '.base64_encode('anyone:wrong')])->assertStatus(401);
    }

    public function test_staging_pages_are_marked_noindex(): void
    {
        config(['site.staging_password' => 'x']);

        $this->get('/')->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_admin_login_page_is_reachable_and_dashboard_requires_sign_in(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
