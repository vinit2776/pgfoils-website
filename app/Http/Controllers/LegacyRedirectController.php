<?php

namespace App\Http\Controllers;

use App\Models\Document;

/** Keeps links to the old site working: filings cited in emails, exchange notices and search results. */
class LegacyRedirectController extends Controller
{
    /** /reports/{id}/{slug}: the old section page -> the matching section (and year) here. */
    public function report(int $id)
    {
        $target = config("legacy.reports.$id");
        abort_unless($target, 404);
        [$slug, $child, $fy] = $target + [null, null, null];

        $url = $child ? route('investors.category', [$slug, $child]) : route('investors.category', $slug);

        return redirect()->to($url.($fy ? '?fy='.$fy : ''), 301);
    }

    /** /storage/...: a document file -> its permanent address; fixed files like the specification PDF. */
    public function file(string $path)
    {
        $path = '/storage/'.ltrim(rawurldecode($path), '/');

        if ($fixed = (config('legacy.files')[$path] ?? null)) {
            return redirect($fixed, 301);
        }
        if (str_starts_with($path, '/storage/csr/') && ! str_contains($path, '/photos/')) {
            return redirect('/docs/csr/'.rawurlencode(basename($path)), 301);
        }

        $docs = Document::live()->where('legacy_path', $path)->get();
        // Folder-style addresses on the old site could point at several documents; send those to the section instead.
        if ($docs->count() === 1) {
            return redirect($docs->first()->url(), 301);
        }
        if ($docs->count() > 1) {
            $d = $docs->first();

            return redirect()->route('investors.category', $d->category->parent ? [$d->category->parent->slug, $d->category->slug] : $d->category->slug, 301);
        }

        abort(404);
    }

    /** /products.html and the other old page names. */
    public function page(string $page)
    {
        $route = config('legacy.pages')[$page.'.html'] ?? null;
        abort_unless($route, 404);

        return redirect()->route($route, [], 301);
    }
}
