<x-layouts.site description="Aluminium foil rolled, laminated and printed in Pipalia Kalan, Rajasthan. Pharmaceutical, food and household foil from a listed Indian manufacturer.">
    <section class="hero">
        <div class="bg" aria-hidden="true"><x-picture name="hero-warehouse" sizes="100vw" :eager="true"/></div>
        <div class="wrap">
            <h1>India’s third&#8209;largest aluminium foil rolling capacity.</h1>
            <p class="lead">Pharmaceutical strip foil, lidding, household and sachet foil, rolled, laminated and printed under one roof in Pipalia Kalan, Rajasthan. <span class="tbs">CLAIM WORDING TO CONFIRM</span></p>
            <div class="actions">
                <a class="btn light" href="{{ route('products') }}">See the product range</a>
                <a class="btn light hide-phone" href="{{ route('quote') }}">Request a quote</a>
            </div>
        </div>
        <div class="gauge" aria-hidden="true"></div>
    </section>

    <section class="block">
        <div class="wrap soon">
            <h2 class="h2">The new site is being built.</h2>
            <p>This is the foundation build. Pages are added in the order set out in the build plan, starting with the Investors Corner.</p>
            <dl class="kv">
                <dt>Plant</dt><dd>{{ config('site.contact.address') }}</dd>
                <dt>Phone</dt><dd>{{ config('site.contact.phone') }}</dd>
                <dt>Email</dt><dd>{{ config('site.contact.email') }}</dd>
            </dl>
        </div>
    </section>
</x-layouts.site>
