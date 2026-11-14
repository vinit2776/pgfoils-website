@props(['name', 'alt' => '', 'sizes' => '100vw', 'eager' => false, 'class' => ''])
@php
    $manifest = once(fn () => is_file(public_path('img/manifest.json'))
        ? json_decode(file_get_contents(public_path('img/manifest.json')), true) : []);
    $m = $manifest[$name] ?? null;
    $srcset = fn (string $ext) => collect($m['widths'] ?? [])->map(fn ($w) => "/img/{$name}-{$w}.{$ext} {$w}w")->implode(', ');
    $fallbackWidth = $m ? (collect($m['widths'])->first(fn ($w) => $w >= 800) ?? end($m['widths'])) : null;
@endphp
@if ($m)
<picture>
    <source type="image/avif" srcset="{{ $srcset('avif') }}" sizes="{{ $sizes }}">
    <source type="image/webp" srcset="{{ $srcset('webp') }}" sizes="{{ $sizes }}">
    <img src="/img/{{ $name }}-{{ $fallbackWidth }}.webp" width="{{ $m['w'] }}" height="{{ $m['h'] }}"
         alt="{{ $alt }}" class="{{ $class }}" decoding="async"
         @if ($eager) fetchpriority="high" @else loading="lazy" @endif>
</picture>
@endif
