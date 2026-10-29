<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_link_in_navigation_and_footer_resolves(): void
    {
        $routes = collect(config('site.nav'))
            ->flatMap(fn ($i) => array_merge([$i['route']], array_column($i['children'] ?? [], 'route')))
            ->merge(collect(config('site.footer'))->flatten(1)->map(fn ($l) => $l[1]))
            ->push(config('site.cta.route'))
            ->unique();

        $this->assertGreaterThan(8, $routes->count());
        foreach ($routes as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_desktop_nav_has_company_dropdown_and_quote_button(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('class="has-sub"', $html);
        foreach (['About and leadership', 'Plants and machinery', 'Clients', 'Certificates'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('head-cta', $html);
    }

    public function test_phone_menu_is_a_native_popover_needing_no_javascript(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression('/<button[^>]+popovertarget="menu"[^>]+aria-label="Open menu"/', $html);
        $this->assertMatchesRegularExpression('/<nav id="menu"[^>]+popover/', $html);
        $this->assertStringContainsString('popovertargetaction="hide"', $html); // visible Close control
    }

    public function test_phone_action_bar_offers_call_and_quote(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('class="action-bar"', $html);
        $this->assertStringContainsString('href="tel:+912937287151"', $html);
    }

    public function test_current_page_is_marked_for_assistive_tech_including_parent_of_a_child_page(): void
    {
        $this->get(route('plants'))->assertSee('aria-current="page"', false);
        $html = $this->get(route('plants'))->getContent();
        // "Company" parent link and the "Plants and machinery" child are both marked current
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'aria-current="page"'));
    }
}
