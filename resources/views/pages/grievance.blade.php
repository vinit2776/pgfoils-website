@php $g = config('content.grievance'); @endphp
<x-layouts.site title="Grievance management" description="How investors can raise and resolve grievances with PG Foils Ltd and its registrar, Bigshare Services.">
    <x-page-head title="Grievance management" trail="Investors" :lead="$g['intro']" />

    <section class="block">
        <div class="wrap">
            <div class="prose"><h2 class="h3">Grievance handling mechanism</h2><p>{{ $g['rta'] }}</p></div>
            <div class="contact-cards" style="margin-top:32px">
                @foreach ($g['contacts'] as [$who, $addr, $tel, $mail])
                    <article class="card">
                        <h3>{{ $who }}</h3>
                        <address>{{ $addr }}</address>
                        <p><a href="tel:{{ preg_replace('/[^+\d]/', '', $tel) }}">{{ $tel }}</a></p>
                        <p><a href="mailto:{{ $mail }}">{{ $mail }}</a></p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.site>
