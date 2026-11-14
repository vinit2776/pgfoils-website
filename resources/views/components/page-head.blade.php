@props(['title', 'lead' => null, 'trail' => null])
<section class="page-head">
    <div class="wrap">
        <p class="crumbs"><a href="{{ route('home') }}">Home</a>@if ($trail) <span aria-hidden="true">/</span> {{ $trail }}@endif</p>
        <h1>{{ $title }}</h1>
        @if ($lead)<p>{{ $lead }}</p>@endif
    </div>
</section>
