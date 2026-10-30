<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Suggests a title, financial year and section from a file name, so staff only correct instead of type. */
class DocumentGuesser
{
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    public static function title(string $fileName): string
    {
        $title = Str::of(pathinfo($fileName, PATHINFO_FILENAME))->replace(['_', '-', '.'], ' ')->squish()->ucfirst()->toString();

        return $title !== '' ? $title : 'Document';
    }

    /** "2025-26" from a name containing it, or from a month and year (the financial year starts in April). */
    public static function fiscalYear(string $fileName): ?string
    {
        $n = strtolower($fileName);
        if (preg_match('/(20\d{2})\s*[-_ ]\s*(\d{2})(?!\d)/', $n, $m) && (((int) substr($m[1], 2)) + 1) % 100 === (int) $m[2]) {
            return $m[1].'-'.$m[2];
        }
        if (preg_match('/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*[-_ .]*(20\d{2})(?!\d)/', $n, $m)) {
            $start = self::MONTHS[$m[1]] >= 4 ? (int) $m[2] : (int) $m[2] - 1;

            return $start.'-'.substr((string) ($start + 1), 2);
        }

        return null;
    }

    /** The one section whose name shares the most words with the file name, or null when unclear. */
    public static function category(string $fileName, Collection $categories): ?Category
    {
        $n = strtolower($fileName);
        $scores = $categories->mapWithKeys(function (Category $c) use ($n) {
            $words = collect(preg_split('/[^a-z0-9]+/', strtolower($c->name)))->filter(fn ($w) => strlen($w) >= 4)->map(fn ($w) => rtrim($w, 's'));

            return [$c->id => $words->filter(fn ($w) => str_contains($n, $w))->count()];
        });
        $best = $scores->max();
        if (! $best) {
            return null;
        }
        $top = $scores->filter(fn ($s) => $s === $best);

        return $top->count() === 1 ? $categories->firstWhere('id', $top->keys()->first()) : null;
    }
}
