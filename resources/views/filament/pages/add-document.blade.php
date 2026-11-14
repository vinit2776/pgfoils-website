<x-filament-panels::page>
    @if ($result)
        <x-filament::section>
            <div style="text-align:center;padding:1.5rem 0">
                @php($n = count($result['created']))
                <h2 style="font-size:1.4rem;font-weight:700">
                    @if ($n === 0) Nothing was added
                    @elseif ($result['when'] === 'now') ✓ {{ $n }} {{ \Illuminate\Support\Str::plural('document', $n) }} live
                    @elseif ($result['when'] === 'schedule') ✓ {{ $n }} {{ \Illuminate\Support\Str::plural('document', $n) }} scheduled
                    @else ✓ {{ $n }} {{ \Illuminate\Support\Str::plural('draft', $n) }} saved
                    @endif
                </h2>
                <ul style="margin:1rem 0">
                    @foreach ($result['created'] as $c)
                        <li>{{ $c['title'] }} @if ($result['when'] === 'now') · <a href="{{ $c['url'] }}" target="_blank" style="text-decoration:underline">View on website</a> @endif</li>
                    @endforeach
                    @foreach ($result['skipped'] as $s)
                        <li style="color:var(--danger-600)">{{ $s['name'] }} was not added: this exact file is already in the list as “{{ $s['title'] }}”.</li>
                    @endforeach
                </ul>
                <div style="display:flex;gap:.75rem;justify-content:center">
                    <x-filament::button wire:click="startOver">Add another</x-filament::button>
                    <x-filament::button color="gray" tag="a" :href="\App\Filament\Resources\Documents\DocumentResource::getUrl()">Go to Documents</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @else
        <form wire:submit="submit">
            {{ $this->form }}
        </form>
    @endif
</x-filament-panels::page>
