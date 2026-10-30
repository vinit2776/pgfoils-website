<x-layouts.site title="Request a quote" description="Request a quote for pharmaceutical, food, household or sachet aluminium foil from PG Foils.">
    <x-page-head title="Request a quote" trail="Products" lead="Tell us what you need. The sales team will reply by email or phone." />

    <section class="block">
        <div class="wrap">
            @if (session('sent'))
                <p class="flash" role="status">Thank you. Your request has been sent and the sales team will get back to you.</p>
            @endif
            <form class="form" method="post" action="{{ route('quote.store') }}">
                @csrf
                <label class="hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                <div class="row">
                    <label>Your name<input name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<span class="err">{{ $message }}</span>@enderror</label>
                    <label>Company<input name="company" value="{{ old('company') }}" autocomplete="organization" required>@error('company')<span class="err">{{ $message }}</span>@enderror</label>
                </div>
                <div class="row">
                    <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<span class="err">{{ $message }}</span>@enderror</label>
                    <label>Phone<input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" required>@error('phone')<span class="err">{{ $message }}</span>@enderror</label>
                </div>
                <div class="row">
                    <label>Product
                        <select name="product" required>
                            <option value="">Choose a product</option>
                            @foreach (config('content.products') as [$name])
                                <option @selected(old('product', request('product')) === $name)>{{ $name }}</option>
                            @endforeach
                            <option @selected(old('product') === 'Other')>Other</option>
                        </select>
                        @error('product')<span class="err">{{ $message }}</span>@enderror
                    </label>
                    <label>Quantity and gauge<input name="quantity" value="{{ old('quantity') }}" placeholder="e.g. 5 tonnes, 20 micron">@error('quantity')<span class="err">{{ $message }}</span>@enderror</label>
                </div>
                <label>Message<textarea name="message" rows="5">{{ old('message') }}</textarea>@error('message')<span class="err">{{ $message }}</span>@enderror</label>
                <p><button class="btn solid" type="submit">Send request</button></p>
            </form>
            <p class="note" style="margin-top:24px">Prefer to talk? Call {{ config('site.contact.phone') }} or email <a class="link-arrow" href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a>.</p>
        </div>
    </section>
</x-layouts.site>
