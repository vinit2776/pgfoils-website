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
                    @if ($fy)<input type="hidden" name="fy" value="{{ $fy }}">@endif
                    <button class="btn solid" type="submit">Search</button>
                </form>
                @if ($years->isNotEmpty())
                    <p class="chips" aria-label="Filter by financial year">
                        <a href="{{ url()->current() }}{{ $q !== '' ? '?q='.urlencode($q) : '' }}" @if (! $fy) aria-current="true" @endif>All years</a>
                        @foreach ($years as $y)
                            <a href="{{ url()->current() }}?fy={{ $y }}{{ $q !== '' ? '&q='.urlencode($q) : '' }}" @if ($fy === $y) aria-current="true" @endif>{{ $y }}</a>
                        @endforeach
                    </p>
                @endif

                @if ($filtered)<p class="note">{{ $total }} {{ Str::plural('document', $total) }} found.</p>@endif
                @forelse ($sections as $s)
                    <section class="ir-sec" id="{{ $s['id'] }}">
                        <h2>@if (! $single)<a href="{{ $s['url'] }}">{{ $s['title'] }}</a>@else{{ $s['title'] }}@endif</h2>
                        @foreach ($s['groups'] as $g)
                            @if ($g['title'])
                                <details @if ($loop->first || $filtered) open @endif>
                                    <summary>{{ $g['title'] }} <span class="n">{{ $g['docs']->count() }} {{ Str::plural('file', $g['docs']->count()) }}</span></summary>
                                    <ul class="docs">@foreach ($g['docs'] as $d) @include('pages.partials.document', ['d' => $d]) @endforeach</ul>
                                </details>
                            @else
                                <ul class="docs flat">@foreach ($g['docs'] as $d) @include('pages.partials.document', ['d' => $d]) @endforeach</ul>
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
