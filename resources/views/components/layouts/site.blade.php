@props(['title' => null, 'description' => null, 'canonical' => null, 'noindex' => false])
@php
    $siteName = config('site.name');
    $pageTitle = $title ? "{$title} | {$siteName}" : $siteName;
    $desc = $description ?? config('site.tagline');
    $canonicalUrl = rtrim(config('site.canonical_url'), '/').($canonical ?? request()->getPathInfo());
    $cta = config('site.cta');
    $tel = 'tel:+912937287151';
    // A nav item is "current" if its route or any child route is the current page.
    $isCurrent = fn (array $item) => request()->routeIs($item['route']) || collect($item['children'] ?? [])->contains(fn ($c) => request()->routeIs($c['route']));
@endphp
<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $desc }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if ($noindex || config('site.staging_password'))<meta name="robots" content="noindex, nofollow">@endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta name="theme-color" content="#1f2733">
    <link rel="preload" href="/fonts/archivo.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/site.css', 'resources/js/site.js'])
    {{ $head ?? '' }}
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="progress" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
<div class="regbar" aria-hidden="true"><i></i><i></i><i></i><i></i></div>

<header class="site-header">
    <div class="wrap">
        <a class="logo" href="{{ route('home') }}" aria-label="PG Foils Ltd, home">
            <x-picture name="logo" alt="PG Foils Ltd." sizes="200px" :eager="true"/>
        </a>

        {{-- Desktop navigation --}}
        <nav class="site-nav" aria-label="Primary">
            @foreach (config('site.nav') as $item)
                @if (! empty($item['children']))
                    <div class="has-sub">
                        <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif>{{ $item['label'] }}<svg class="chev" width="10" height="6" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></a>
                        <div class="sub">
                            @foreach ($item['children'] as $child)
                                <a href="{{ route($child['route']) }}" @if (request()->routeIs($child['route'])) aria-current="page" @endif>{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ route($item['route']) }}" @if ($isCurrent($item)) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endif
            @endforeach
        </nav>

        <div class="head-actions">
            <a class="head-tel" href="{{ $tel }}" aria-label="Call the plant">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15 15 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.6a1 1 0 01-.25 1z" fill="currentColor"/></svg>
                <span class="head-tel-num">{{ config('site.contact.phone') }}</span>
            </a>
            <a class="btn solid sm head-cta" href="{{ route($cta['route']) }}">{{ $cta['label'] }}</a>
            {{-- Phone menu: native popover, no JavaScript. Esc and tapping outside close it. --}}
            <button class="menu-btn" type="button" popovertarget="menu" aria-label="Open menu">
                <svg width="20" height="14" viewBox="0 0 20 14" aria-hidden="true"><path d="M1 1h18M1 7h18M1 13h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
                <span>Menu</span>
            </button>
        </div>
    </div>
</header>

<nav id="menu" class="menu" popover aria-label="Menu">
    <div class="menu-head">
        <span class="label">Menu</span>
        <button class="menu-close" type="button" popovertarget="menu" popovertargetaction="hide" aria-label="Close menu">Close</button>
    </div>
    <ul class="menu-list">
        @foreach (config('site.nav') as $item)
            <li>
                <a class="menu-link" href="{{ route($item['route']) }}" @if ($isCurrent($item) && empty($item['children'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @if (! empty($item['children']))
                    <ul class="menu-sub">
                        @foreach ($item['children'] as $child)
                            <li><a href="{{ route($child['route']) }}" @if (request()->routeIs($child['route'])) aria-current="page" @endif>{{ $child['label'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
        <li><a class="menu-link" href="{{ route('contact') }}">Contact</a></li>
    </ul>
    <div class="menu-foot">
        <a class="btn solid" href="{{ route($cta['route']) }}">{{ $cta['label'] }}</a>
        <a class="btn" href="{{ $tel }}">Call {{ config('site.contact.phone') }}</a>
    </div>
</nav>

<main id="main">
    {{ $slot }}
</main>

<footer class="site-footer">
    <div class="regbar" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    <div class="wrap foot-grid">
        <div class="foot-about">
            <strong>{{ $siteName }}</strong>
            <address>
                <span>{{ config('site.contact.address') }}</span>
                <a href="{{ $tel }}">{{ config('site.contact.phone') }}</a>
                <a href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a>
            </address>
        </div>
        @foreach (config('site.footer') as $heading => $links)
            <div class="foot-col">
                <h2 class="label">{{ $heading }}</h2>
                <ul>
                    @foreach ($links as [$label, $route])
                        <li><a href="{{ route($route) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
    <div class="wrap foot-legal">© {{ date('Y') }} {{ $siteName }}</div>
</footer>

{{-- Phone action bar: the two things a buyer does, within thumb reach --}}
<div class="action-bar">
    <a class="btn" href="{{ $tel }}">Call</a>
    <a class="btn solid" href="{{ route($cta['route']) }}">{{ $cta['label'] }}</a>
</div>
</body>
</html>
