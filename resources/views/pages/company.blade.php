@php $c = config('content'); @endphp
<x-layouts.site title="About PG Foils" description="PG Foils Ltd: third largest aluminium foil rolling capacity in India, led by the Shah family from Pipalia Kalan, Rajasthan.">
    <x-page-head title="About PG Foils" trail="Company" lead="A family-led aluminium foil mill in Rajasthan, with the third largest rolling capacity in India." />

    <section class="block">
        <div class="wrap two">
            <div class="prose">
                <h2 class="h2">Who we are</h2>
                @foreach ($c['about'] as $p)<p>{{ $p }}</p>@endforeach
            </div>
            <x-picture name="about-team" alt="The PG Foils team" sizes="(min-width: 900px) 50vw, 100vw"/>
        </div>
    </section>

    <section class="block alt">
        <div class="wrap">
            <h2 class="h2 sec-head">Board of Directors</h2>
            <div class="people">
                @foreach ($c['directors'] as $d)
                    <article class="person">
                        <x-picture :name="$d['image']" :alt="$d['name']" sizes="(min-width: 900px) 30vw, 90vw"/>
                        <h3>{{ $d['name'] }}</h3>
                        <p class="role">{{ $d['role'] }}</p>
                        <p>{{ $d['bio'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="block">
        <div class="wrap two">
            <div class="prose">
                <h2 class="h2">Quality system</h2>
                <p>{{ $c['quality'] }}</p>
                <p><a class="link-arrow" href="{{ route('plants') }}">Plants and machinery</a></p>
            </div>
            <x-picture name="plant-exterior" alt="PG Foils plant at Pipalia Kalan" sizes="(min-width: 900px) 50vw, 100vw"/>
        </div>
    </section>

    <section class="block alt">
        <div class="wrap">
            <h2 class="h2 sec-head">Global presence</h2>
            <x-picture name="exports-map" alt="Map of the countries PG Foils exports to" sizes="(min-width: 1280px) 1200px, 100vw"/>
        </div>
    </section>

    <section class="block" id="branches">
        <div class="wrap">
            <h2 class="h2 sec-head">Branches</h2>
            <div class="branches">
                @foreach ($c['branches'] as [$city, $addr, $mob, $mail])
                    <article class="branch">
                        <h3>{{ $city }}</h3>
                        <address>{{ $addr }}</address>
                        <a href="tel:{{ preg_replace('/[^+\d]/', '', $mob) }}">{{ $mob }}</a>
                        @if ($mail)<br><a href="mailto:{{ $mail }}">{{ $mail }}</a>@endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.site>
