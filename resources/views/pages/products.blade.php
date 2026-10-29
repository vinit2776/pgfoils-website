@php $c = config('content'); @endphp
<x-layouts.site title="Products" description="Pharmaceutical strip and blister foil, lidding, cold forming Alu-Alu, household, confectionery and sachet foil from PG Foils.">
    <x-page-head title="Our products" trail="Products" lead="Rolled, laminated and printed aluminium foil for pharma, food and household use." />

    <section class="block">
        <div class="wrap">
            <p><a class="btn" href="/docs/product-specifications.pdf" target="_blank" rel="noopener">Download specifications (PDF)</a></p>
            <div class="tiles" style="margin-top:28px">
                @foreach ($c['products'] as [$name, $img])
                    <figure class="tile" style="margin:0">
                        <x-picture :name="$img" :alt="$name" sizes="(min-width: 700px) 33vw, 50vw"/>
                        <figcaption>{{ $name }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <p style="margin-top:32px"><a class="btn solid" href="{{ route('quote') }}">Request a quote</a></p>
        </div>
    </section>
</x-layouts.site>
