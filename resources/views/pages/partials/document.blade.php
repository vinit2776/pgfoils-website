@if ($d->isMissingFile())
<li><span class="row miss"><span class="ty">{{ $d->file_type ?: 'file' }}</span><span class="t">{{ $d->title }}</span><span class="when">File being restored. Please contact the company for a copy.</span></span></li>
@else
<li><a href="{{ $d->url() }}" target="_blank" rel="noopener"><span class="ty">{{ $d->file_type ?: 'file' }}</span><span class="t">{{ $d->title }}</span>@php $meta = collect([$d->last_updated_on_site ? 'Updated '.$d->last_updated_on_site->format('j M Y') : null, $d->sizeLabel()])->filter()->implode(' · '); @endphp @if ($meta)<span class="when">{{ $meta }}</span>@endif</a></li>
@endif
