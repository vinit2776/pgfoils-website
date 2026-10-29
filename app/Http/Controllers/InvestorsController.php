<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvestorsController extends Controller
{
    public function index(Request $request)
    {
        return $this->render($request, Category::visible()->topLevel()->get());
    }

    public function category(Request $request, string $category, ?string $child = null)
    {
        $top = Category::visible()->topLevel()->where('slug', $category)->firstOrFail();
        if ($child) {
            $sub = $top->children()->visible()->where('slug', $child)->firstOrFail();

            return $this->render($request, collect([$sub]), $top);
        }

        return $this->render($request, collect([$top]));
    }

    private function render(Request $request, $categories, ?Category $parent = null)
    {
        $q = trim((string) $request->query('q'));
        $fy = $request->query('fy');

        $docs = fn ($category) => Document::live()->where('category_id', $category->id)
            ->when($q !== '', fn ($w) => $w->where('title', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->when($fy, fn ($w) => $w->where('fiscal_year', $fy))
            ->orderByDesc('fiscal_year')->orderBy('sort_order')->orderByDesc('id')->get();

        $sections = $categories->map(function (Category $c) use ($docs) {
            $groups = [];
            $children = $c->children()->visible()->get();
            if ($children->isNotEmpty()) {
                foreach ($children as $child) {
                    $groups[] = ['title' => $child->name, 'url' => route('investors.category', [$c->slug, $child->slug]), 'docs' => $docs($child)];
                }
            } elseif ($c->group_by_year) {
                foreach ($docs($c)->groupBy(fn ($d) => $d->fiscal_year ?: 'Other') as $year => $list) {
                    $groups[] = ['title' => $year === 'Other' ? 'Other' : 'FY '.$year, 'docs' => $list];
                }
            } else {
                $groups[] = ['title' => null, 'docs' => $docs($c)];
            }

            return ['id' => $c->slug, 'title' => $c->name, 'url' => route('investors.category', $c->slug), 'groups' => collect($groups)->filter(fn ($g) => $g['docs']->isNotEmpty())->values()];
        })->filter(fn ($s) => $s['groups']->isNotEmpty() || ($q === '' && ! $fy));

        $years = Document::live()->whereNotNull('fiscal_year')->distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year');

        return view('pages.investors', [
            'sections' => $sections->values(),
            'menu' => Category::visible()->topLevel()->get(),
            'q' => $q, 'fy' => $fy, 'years' => $years, 'single' => request()->route('category') !== null,
            'parent' => $parent,
        ]);
    }

    /** Stable download address for a document. Only live documents are served. */
    public function document(Document $document, ?string $slug = null)
    {
        abort_unless($document->isLive(), 404);

        if ($document->file_path && Storage::disk('investor')->exists($document->file_path)) {
            $name = $document->slug().'.'.($document->file_type ?: 'pdf');

            return Storage::disk('investor')->response($document->file_path, $name, [
                'Content-Disposition' => 'inline; filename="'.$name.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort_unless($document->external_url, 404);
        [$scheme, $rest] = explode('://', $document->external_url, 2);
        $parts = explode('/', $rest);
        $host = array_shift($parts);

        return redirect()->away($scheme.'://'.$host.'/'.implode('/', array_map('rawurlencode', $parts)));
    }

    public function sitemap()
    {
        $urls = collect(['home', 'products', 'company', 'plants', 'clients', 'sustainability', 'certificates', 'investors', 'grievance', 'contact', 'quote'])
            ->map(fn ($r) => route($r));
        foreach (Category::visible()->topLevel()->get() as $c) {
            $urls->push(route('investors.category', $c->slug));
            foreach ($c->children()->visible()->get() as $child) {
                $urls->push(route('investors.category', [$c->slug, $child->slug]));
            }
        }
        $base = rtrim(config('site.canonical_url'), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$urls->map(fn ($u) => '<url><loc>'.e($base.parse_url($u, PHP_URL_PATH)).'</loc></url>')->implode('').'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
