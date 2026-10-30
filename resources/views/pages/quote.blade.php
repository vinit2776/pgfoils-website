@php
    $mail = config('site.contact.sales_inbox') ?: config('site.contact.email');
    $body = "Hello PG Foils team,\n\nI would like a quote.\n\nProduct: \nGauge / thickness: \nQuantity: \nDelivery location: \n\nName: \nCompany: \nPhone: \n";
    $link = fn (string $subject) => 'mailto:'.$mail.'?subject='.rawurlencode($subject).'&body='.rawurlencode($body);
@endphp
<x-layouts.site title="Request a quote" description="Request a quote for pharmaceutical, food, household or sachet aluminium foil from PG Foils.">
    <x-page-head title="Request a quote" trail="Products" lead="Choose a product and your email app opens with the request ready to send." />

    <section class="block">
        <div class="wrap">
            <p><a class="btn solid" href="{{ $link('Quote request') }}">Email us a general request</a></p>
            <h2 class="h3" style="margin-top:40px">Or start from a product</h2>
            <ul class="docs">
                @foreach (config('content.products') as [$name])
                    <li><a href="{{ $link('Quote request: '.$name) }}">{{ $name }}</a></li>
                @endforeach
            </ul>
            <p class="note" style="margin-top:24px">Prefer to talk? Call <a class="link-arrow" href="tel:+912937287151">{{ config('site.contact.phone') }}</a> or write to <a class="link-arrow" href="mailto:{{ $mail }}">{{ $mail }}</a>. If no email app opens, send your request to that address.</p>
        </div>
    </section>
</x-layouts.site>
