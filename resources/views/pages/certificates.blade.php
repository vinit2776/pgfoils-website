@php $c = config('content'); @endphp
<x-layouts.site title="Certificates" description="Quality, food safety and regulatory approvals held by PG Foils Ltd.">
    <x-page-head title="Certificates and approvals" trail="Certificates" lead="Approvals and registrations held by PG Foils." />

    <section class="block">
        <div class="wrap">
            <div class="tiles t4">
                @foreach ($c['certificates'] as [$n, $label])
                    <figure class="tile" style="margin:0">
                        <x-picture :name="'cert-'.$n" :alt="$label" sizes="(min-width: 1000px) 25vw, 50vw"/>
                        <figcaption>{{ $label }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <p class="note" style="margin-top:24px">Issuer, scope and validity dates are to be supplied by the company. <span class="tbs">TO BE SUPPLIED</span></p>
        </div>
    </section>
</x-layouts.site>
