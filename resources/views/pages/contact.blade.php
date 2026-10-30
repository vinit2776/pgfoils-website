@php $k = config('site.contact'); @endphp
<x-layouts.site title="Contact" description="Contact PG Foils Ltd at Pipalia Kalan, Pali, Rajasthan, or through a branch office.">
    <x-page-head title="Contact" trail="Contact" lead="Call, email or write to the plant, or reach the nearest branch." />

    <section class="block">
        <div class="wrap">
            <div class="contact-cards">
                <article class="card">
                    <h3>Registered office and plant</h3>
                    <address>M/s PG Foils Limited<br>P.O. Pipalia Kalan 306 307<br>Dist. Pali, Rajasthan</address>
                    <p>Phone: <a href="tel:+912937287151">{{ $k['phone'] }}</a></p>
                    <p>Fax: +91-2937-287150</p>
                    <p>Mobile: <a href="tel:+919772981111">+91-97729-81111</a> / <a href="tel:+919772982222">97729-82222</a></p>
                    <p>Email: <a href="mailto:{{ $k['email'] }}">{{ $k['email'] }}</a></p>
                </article>
                <article class="card">
                    <h3>Quotes</h3>
                    <p>Request a quote and the sales team will reply.</p>
                    <p><a class="btn solid" href="{{ route('quote') }}">Request a quote</a></p>
                </article>
                <article class="card">
                    <h3>Branches</h3>
                    <p>Mumbai, New Delhi, Kolkata, Bangalore, Hyderabad, Indore, Ahmedabad, Jaipur, Chennai and Beawar.</p>
                    <p><a href="{{ route('company') }}#branches">See branch addresses</a></p>
                </article>
            </div>
        </div>
    </section>
</x-layouts.site>
