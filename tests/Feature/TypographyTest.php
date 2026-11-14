<?php

namespace Tests\Feature;

use Tests\TestCase;

class TypographyTest extends TestCase
{
    public function test_site_uses_one_self_hosted_font_file_under_the_size_budget(): void
    {
        $files = glob(public_path('fonts/*.woff2'));

        $this->assertCount(1, $files, 'one font family, one file');
        $this->assertLessThan(60 * 1024, filesize($files[0]), 'font file must stay under 60 KB');
    }

    public function test_only_that_font_is_preloaded_and_no_third_party_font_hosts_are_used(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertSame(1, substr_count($html, 'as="font"'));
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('fonts.bunny.net', $html);
    }
}
