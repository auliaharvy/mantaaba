@props(['name', 'position', 'division' => null, 'photo' => null])
<div class="flex items-center gap-4 rounded-lg border border-brand-border bg-white p-4">
    @if ($photo)
        <img src="{{ $photo }}" alt="Foto {{ $name }}" class="h-16 w-16 flex-none rounded-full object-cover" loading="lazy">
    @else
        <div class="flex h-16 w-16 flex-none items-center justify-center rounded-full bg-brand-section-bg text-lg font-bold text-brand-accent" aria-hidden="true">
            {{ collect(explode(' ', $name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
        </div>
    @endif
    <div class="min-w-0">
        <p class="font-bold text-brand-heading">{{ $name }}</p>
        <p class="text-sm font-semibold text-brand-accent">{{ $position }}</p>
        @if ($division)<p class="text-sm text-gray-600">{{ $division }}</p>@endif
    </div>
</div>
