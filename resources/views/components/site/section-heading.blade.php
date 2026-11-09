@props(['eyebrow' => null, 'title', 'description' => null, 'center' => false])
<div {{ $attributes->merge(['class' => 'mb-8 ' . ($center ? 'mx-auto max-w-2xl text-center' : '')]) }}>
    @if ($eyebrow)<p class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-secondary">{{ $eyebrow }}</p>@endif
    <h2 class="text-2xl sm:text-3xl">{{ $title }}</h2>
    @if ($description)<p class="mt-3 text-gray-600">{{ $description }}</p>@endif
</div>
