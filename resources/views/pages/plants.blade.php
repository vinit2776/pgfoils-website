@php $c = config('content'); @endphp
<x-layouts.site title="Plants and machinery" description="Rolling mills, foil separators, laminators, printing and slitting machines at the PG Foils plant in Pipalia Kalan, Rajasthan.">
    <x-page-head title="Plants and machinery" trail="Plants" :lead="$c['machinery_intro']" />

    <section class="block">
        <div class="wrap two">
            <div>
                <h2 class="h3">Plant and machinery</h2>
                <div class="table-wrap">
                    <table class="spec">
                        <thead><tr><th>Origin</th><th>Make</th><th>Equipment</th></tr></thead>
                        <tbody>
                            @foreach ($c['machinery'] as [$origin, $make, $what])
                                <tr><td>{{ $origin }}</td><td>{{ $make }}</td><td>{{ $what }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <h2 class="h3">In-house supporting facilities</h2>
                <ul class="ticks">@foreach ($c['support'] as $s)<li>{{ $s }}</li>@endforeach</ul>
            </div>
        </div>
    </section>

    @foreach ($c['plants'] as $n => $count)
        <section class="block {{ $n % 2 ? 'alt' : '' }}">
            <div class="wrap">
                <h2 class="h3 sec-head">Plant {{ $n }}</h2>
                <ul class="gallery">
                    @for ($i = 1; $i <= $count; $i++)
                        <li><x-picture :name="'plant'.$n.'-'.$i" :alt="'Plant '.$n.', photo '.$i" sizes="(min-width: 700px) 25vw, 50vw"/></li>
                    @endfor
                </ul>
            </div>
        </section>
    @endforeach
</x-layouts.site>
