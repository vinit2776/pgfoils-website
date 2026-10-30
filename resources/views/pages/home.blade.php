@php $c = config('content'); $k = config('site.contact'); @endphp
<x-layouts.site description="Aluminium foil rolled, laminated and printed in Pipalia Kalan, Rajasthan. Pharmaceutical, food and household foil from a listed Indian manufacturer.">
    <section class="hero">
        <div class="bg" aria-hidden="true"><x-picture name="hero-warehouse" sizes="100vw" :eager="true"/></div>
        <div class="wrap">
            <h1>India’s third&#8209;largest aluminium foil rolling capacity.</h1>
            <p class="lead">Pharmaceutical strip foil, lidding, household and sachet foil, rolled, laminated and printed under one roof in Pipalia Kalan, Rajasthan.</p>
            <div class="actions">
                <a class="btn light" href="{{ route('products') }}">See the product range</a>
                <a class="btn light hide-phone" href="{{ route('quote') }}">Request a quote</a>
            </div>
            <p class="makes"><span>Rolled and converted on</span> <b>Achenbach</b> <b>Davy United</b> <b>Smutze</b> <b>Kampf</b> <b>Black Clawson</b> <b>Nakajima</b> <b>Sungan</b></p>
        </div>
        <div class="gauge" aria-hidden="true"></div>
    </section>

    <section class="certband" aria-label="Approvals and certifications">
        <div class="wrap">
            <p class="lbl">Approvals and audits</p>
            <ul class="cert-row">
                @foreach (array_slice($c['certificates'], 0, 6) as [$n, $label])
                    <li><x-picture :name="'cert-'.$n" :alt="$label" sizes="120px"/></li>
                @endforeach
            </ul>
            <a class="link-arrow" href="{{ route('certificates') }}">All certificates</a>
        </div>
    </section>

    <section class="block">
        <div class="wrap">
            <div class="sec-head">
                <h2 class="h2">Nine foil formats, one supplier.</h2>
                <p class="sub">Used across pharmaceutical, food and beverage, communications, air-conditioning and health products.</p>
            </div>
            <div class="tiles t3">
                @foreach (array_slice($c['products'], 0, 6) as [$name, $img])
                    <figure class="tile" style="margin:0">
                        <x-picture :name="$img" :alt="$name" sizes="(min-width: 900px) 30vw, 50vw"/>
                        <figcaption>{{ $name }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <p class="row-links">
                <a class="btn solid" href="{{ route('products') }}">All nine products</a>
                <a class="link-arrow" href="/docs/product-specifications.pdf" target="_blank" rel="noopener">Download specifications (PDF)</a>
            </p>
        </div>
    </section>

    <section class="block alt">
        <div class="wrap two">
            <x-picture name="about-team" alt="The PG Foils team" sizes="(min-width: 900px) 50vw, 100vw"/>
            <div class="prose">
                <h2 class="h2">Three generations, one desert plant.</h2>
                <p>{{ $c['about'][1] }}</p>
                <p>{{ $c['about'][2] }}</p>
                <p><a class="link-arrow" href="{{ route('company') }}">About the company and board</a></p>
            </div>
        </div>
    </section>

    <section class="block">
        <div class="wrap">
            <div class="sec-head">
                <h2 class="h2">The machine floor, with makers named.</h2>
                <p class="sub">{{ $c['machinery_intro'] }}</p>
            </div>
            <div class="table-wrap">
                <table class="spec">
                    <thead><tr><th>Origin</th><th>Make</th><th>Equipment</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($c['machinery'], 0, 6) as [$origin, $make, $what])
                            <tr><td>{{ $origin }}</td><td>{{ $make }}</td><td>{{ $what }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="row-links"><a class="link-arrow" href="{{ route('plants') }}">Plants, machinery and photo galleries</a></p>
        </div>
    </section>

    <section class="block alt">
        <div class="wrap">
            <div class="sec-head">
                <h2 class="h2">Packed for the brands on your shelf.</h2>
                <p class="sub">Food and pharmaceutical companies across India rely on PG Foils.</p>
            </div>
            <div class="tiles t6">
                @foreach ([['food', [1, 2, 3, 4, 5, 6]], ['pharma', [1, 2, 3, 4, 5, 6]]] as [$key, $ids])
                    @foreach ($ids as $i)
                        <figure class="tile logo" style="margin:0"><x-picture :name="'cl-'.$key.'-'.$i" :alt="ucfirst($key).' client'" sizes="(min-width: 1000px) 16vw, 33vw"/></figure>
                    @endforeach
                @endforeach
            </div>
            <p class="row-links"><a class="link-arrow" href="{{ route('clients') }}">See all 52 clients</a></p>
        </div>
    </section>

    <section class="block dark">
        <div class="wrap two">
            <div>
                <h2 class="h2">Shipped to 13 countries.</h2>
                <ul class="countries">
                    @foreach (['UAE', 'Thailand', 'Russia', 'USA', 'UK', 'Nigeria', 'Sri Lanka', 'Egypt', 'Philippines', 'Bangladesh', 'Oman', 'Nepal', 'Kenya'] as $cn)<li>{{ $cn }}</li>@endforeach
                </ul>
            </div>
            <div class="map"><x-picture name="exports-map" alt="Map of the countries PG Foils exports to" sizes="(min-width: 900px) 50vw, 100vw"/></div>
        </div>
    </section>

    <section class="block">
        <div class="wrap two">
            <div class="prose">
                <h2 class="h2">Power from the desert sun and wind.</h2>
                <p>PG Foils runs a 1.5 MW Suzlon and a 0.6 MW Wind World windmill in Jaisalmer and a 2 MW solar plant at Pipalia Kalan, with another 1 MW solar plant under way. Through the Paras Raj Bohra Memorial Trust it also supports a hospital and a mobile hospital.</p>
                <p><a class="link-arrow" href="{{ route('sustainability') }}">Green initiative and CSR</a></p>
            </div>
            <x-picture name="green-1" alt="Wind turbines in Jaisalmer" sizes="(min-width: 900px) 50vw, 100vw"/>
        </div>
    </section>

    <section class="block alt">
        <div class="wrap two">
            <div class="prose">
                <h2 class="h2">Investors Corner</h2>
                <p>Financial results, annual reports, shareholding pattern, credit ratings, corporate governance and stock-exchange announcements, in one searchable place.</p>
                <p><a class="btn solid" href="{{ route('investors') }}">Open Investors Corner</a></p>
            </div>
            <div class="card">
                <h3>Registered office and plant</h3>
                <address>M/s PG Foils Limited<br>{{ $k['address'] }}</address>
                <p>Phone: <a href="tel:+912937287151">{{ $k['phone'] }}</a></p>
                <p>Email: <a href="mailto:{{ $k['email'] }}">{{ $k['email'] }}</a></p>
                <p><a href="{{ route('contact') }}">Contact and branches</a></p>
            </div>
        </div>
    </section>

    <section class="cta-band">
        <div class="wrap">
            <h2>Need foil for your next run?</h2>
            <p>Tell us the product, gauge and quantity. The sales team will reply.</p>
            <a class="btn light" href="{{ route('quote') }}">Request a quote</a>
        </div>
    </section>
</x-layouts.site>
