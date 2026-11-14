@php
    $filtered = $q !== '' || $fy;
    $total = $sections->sum(fn ($s) => $s['groups']->sum(fn ($g) => $g['docs']->count()));
    $heading = $single ? ($parent ? $parent->name.' › ' : '').$sections->first()['title'] : null;
@endphp
<x-layouts.site :title="$heading ?? 'Investors Corner'" description="Annual reports, financial results, stock exchange announcements, shareholding pattern and other disclosures of PG Foils Limited.">
    <x-page-head :title="$heading ?? 'Investors Corner'" trail="Investors" lead="Filings and disclosures of PG Foils Limited. For shareholder service, see grievance management." />

    <section class="block">
        <div class="wrap ir">
            <nav class="ir-nav" aria-label="Investors Corner sections">
                <h2>Sections</h2>
                <ul>
                    @foreach ($menu as $m)
                        <li><a href="{{ $single ? route('investors.category', $m->slug) : '#'.$m->slug }}">{{ $m->name }}</a></li>
                    @endforeach
                    <li><a href="{{ route('grievance') }}">Grievance management</a></li>
                    @if ($single)<li><a href="{{ route('investors') }}">All sections</a></li>@endif
                </ul>
            </nav>

            <div>
                <form class="ir-search" method="get" role="search" action="{{ url()->current() }}">
                    <label class="visually-hidden" for="q">Search documents by title</label>
                    <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Search by title">
                    @if ($years->isNotEmpty())
                        <label class="visually-hidden" for="fy">Financial year</label>
                        <select id="fy" name="fy">
                            <option value="">All years</option>
                            @foreach ($years as $y)<option value="{{ $y }}" @selected($fy === $y)>{{ $y }}</option>@endforeach
                        </select>
                    @endif
                    <button class="btn solid" type="submit">Search</button>
                    @if ($filtered)<a class="clear" href="{{ url()->current() }}">Clear</a>@endif
                </form>

                @if ($filtered)<p class="note">{{ $total }} {{ Str::plural('document', $total) }} found.</p>@endif
                @forelse ($sections as $s)
                    <section class="ir-sec" id="{{ $s['id'] }}">
                        <h2>@if (! $single)<a href="{{ $s['url'] }}">{{ $s['title'] }}</a>@else{{ $s['title'] }}@endif</h2>
                        @foreach ($s['groups'] as $g)
                            @if ($g['title'])
                                <details @if ($loop->first || $filtered) open @endif>
                                    <summary>{{ $g['title'] }} <span class="n">{{ $g['docs']->count() }} {{ Str::plural('file', $g['docs']->count()) }}</span></summary>
                                    <ul class="docs @if ($g['docs']->count() >= 3 && $g['docs']->count() <= 16) cols @endif">@foreach ($g['docs'] as $d) @include('pages.partials.document', ['d' => $d]) @endforeach</ul>
                                </details>
                            @else
                                @if ($g['docs']->count() > 2 && $g['docs']->every(fn ($d) => $d->yearLabel()))
                                    <table class="ledger">
                                        <thead><tr><th scope="col">Year</th><th scope="col">Document</th><th scope="col" class="sz">Size</th><th scope="col"><span class="visually-hidden">Download</span></th></tr></thead>
                                        <tbody>
                                            @foreach ($g['docs'] as $d)
                                                <tr>
                                                    <th scope="row">{{ $d->yearLabel() }}</th>
                                                    <td>{{ $d->title }}</td>
                                                    <td class="sz">{{ strtoupper($d->file_type ?: 'file') }}@if ($d->sizeLabel()) · {{ $d->sizeLabel() }}@endif</td>
                                                    <td class="go"><a href="{{ $d->url() }}" target="_blank" rel="noopener" aria-label="Download {{ $d->title }}">Download <span aria-hidden="true">↓</span></a></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <ul class="docs flat @if ($g['docs']->count() >= 3 && $g['docs']->count() <= 16) cols @endif">@foreach ($g['docs'] as $d) @include('pages.partials.document', ['d' => $d]) @endforeach</ul>
                                @endif
                            @endif
                        @endforeach
                    </section>
                @empty
                    <p>No documents match. <a class="link-arrow" href="{{ url()->current() }}">Clear the search</a></p>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.site>
