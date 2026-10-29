<x-layouts.site :title="$label">
    <section class="block">
        <div class="wrap soon">
            <h1 class="h2">{{ $label }}</h1>
            <p>This page is part of the new site and is not built yet. For anything urgent, call {{ config('site.contact.phone') }} or email <a class="link-arrow" href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a>.</p>
            <p><a class="btn" href="{{ route('home') }}">Back to home</a></p>
        </div>
    </section>
</x-layouts.site>
