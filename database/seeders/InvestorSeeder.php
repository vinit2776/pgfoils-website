<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Loads the Investors Corner as it is on the current site (resources/data/investors.json): categories, and every
 * document as Published with its address on the current site. M4 replaces those addresses with files on this site.
 */
class InvestorSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(resource_path('data/investors.json')), true);
        $byYear = ['financial-results', 'shareholding'];
        $order = 0;

        foreach ($data as $section) {
            $isAnnouncements = $section['id'] === 'announcements';
            $top = Category::firstOrCreate(
                ['parent_id' => null, 'slug' => $section['id']],
                ['name' => $section['title'], 'sort_order' => ++$order * 10, 'group_by_year' => in_array($section['id'], $byYear, true)],
            );

            foreach ($section['groups'] as $i => $group) {
                $cat = $top;
                $fy = null;
                if ($isAnnouncements) {
                    $cat = Category::firstOrCreate(['parent_id' => $top->id, 'slug' => Str::slug($group['title'])], ['name' => $group['title'], 'sort_order' => ($i + 1) * 10]);
                } elseif ($group['title'] && str_starts_with($group['title'], 'FY ')) {
                    $fy = substr($group['title'], 3);
                }

                foreach ($group['docs'] as $j => $doc) {
                    Document::withoutEvents(fn () => Document::firstOrCreate(
                        ['external_url' => $doc['url'], 'title' => $doc['title'], 'category_id' => $cat->id],
                        ['fiscal_year' => $fy, 'status' => Document::PUBLISHED,
                            'published_at' => now(), 'legacy_path' => rawurldecode(parse_url($doc['url'], PHP_URL_PATH)), 'original_name' => basename(parse_url($doc['url'], PHP_URL_PATH)), 'file_type' => $doc['type'], 'sort_order' => $j],
                    ));
                }
            }
        }
    }
}
