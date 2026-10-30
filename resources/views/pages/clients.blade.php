<x-layouts.site title="Clients" description="Food and pharmaceutical companies that rely on PG Foils aluminium foil.">
    <x-page-head title="Our clients" trail="Clients" lead="Food and pharmaceutical companies that pack in PG Foils foil." />

    @foreach ([['food', 28, 'Food industries'], ['pharma', 24, 'Pharma industries']] as [$key, $count, $label])
        <section class="block {{ $key === 'pharma' ? 'alt' : '' }}">
            <div class="wrap">
                <h2 class="h3 sec-head">{{ $label }}</h2>
                <div class="tiles t6">
                    @for ($i = 1; $i <= $count; $i++)
                        <figure class="tile logo"><x-picture :name="'cl-'.$key.'-'.$i" :alt="$label.' client '.$i" sizes="(min-width: 1000px) 16vw, 33vw"/></figure>
                    @endfor
                </div>
            </div>
        </section>
    @endforeach
</x-layouts.site>
