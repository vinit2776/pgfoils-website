<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImagePipelineTest extends TestCase
{
    public function test_images_build_produces_avif_webp_and_manifest(): void
    {
        $this->assertSame(0, Artisan::call('images:build'));

        $manifest = json_decode(file_get_contents(public_path('img/manifest.json')), true);
        $this->assertArrayHasKey('hero-warehouse', $manifest);

        foreach ($manifest['hero-warehouse']['widths'] as $w) {
            $this->assertFileExists(public_path("img/hero-warehouse-{$w}.avif"));
            $this->assertFileExists(public_path("img/hero-warehouse-{$w}.webp"));
        }
    }

    public function test_hero_image_is_far_smaller_than_the_original_jpeg(): void
    {
        Artisan::call('images:build');
        $original = filesize(resource_path('images/source/hero-warehouse.jpg'));
        $served = filesize(public_path('img/hero-warehouse-1366.avif'));

        $this->assertLessThan($original / 4, $served, 'AVIF should be under a quarter of the source JPEG');
    }

    public function test_picture_component_outputs_responsive_markup_with_dimensions(): void
    {
        Artisan::call('images:build');
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('type="image/avif"', $html);
        $this->assertMatchesRegularExpression('/<img[^>]+width="1366"[^>]+height="660"/', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
    }
}
