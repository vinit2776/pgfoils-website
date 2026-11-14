@php $c = config('content'); @endphp
<x-layouts.site title="Sustainability and CSR" description="PG Foils wind and solar power, and the Paras Raj Bohra Memorial Trust's school, hospital and mobile hospital.">
    <x-page-head title="Sustainability and CSR" trail="Sustainability" lead="Renewable power at the plant, and the charitable trust set up in memory of our founder." />

    <section class="block" id="green">
        <div class="wrap">
            <h2 class="h2 sec-head">Green initiative</h2>
            <p class="prose" style="max-width:68ch">{{ $c['green'] }}</p>
            <ul class="gallery" style="margin-top:28px">
                @for ($i = 1; $i <= 4; $i++)
                    <li><x-picture :name="'green-'.$i" :alt="'Green initiative, photo '.$i" sizes="(min-width: 700px) 25vw, 50vw"/></li>
                @endfor
            </ul>
        </div>
    </section>

    <section class="block alt" id="csr">
        <div class="wrap">
            <h2 class="h2 sec-head">Corporate social responsibility</h2>
            <div class="two">
                <div class="prose">@foreach ($c['csr'] as $p)<p>{{ $p }}</p>@endforeach</div>
                <x-picture name="csr-hero" alt="Paras Raj Bohra Memorial Trust" sizes="(min-width: 900px) 50vw, 100vw"/>
            </div>
            <h3 class="h3" style="margin-top:40px">CSR reports and policy</h3>
            <ul class="docs">
                @foreach ($c['csr_docs'] as [$label, $file])
                    <li><a href="/docs/csr/{{ rawurlencode($file) }}" target="_blank" rel="noopener"><span class="ty">pdf</span>{{ $label }}</a></li>
                @endforeach
            </ul>
            <ul class="gallery" style="margin-top:28px">
                @for ($i = 0; $i <= 12; $i++)
                    <li><x-picture :name="'csr-'.$i" :alt="'CSR activity, photo '.($i + 1)" sizes="(min-width: 700px) 25vw, 50vw"/></li>
                @endfor
            </ul>
        </div>
    </section>
</x-layouts.site>
