@props(['eyebrow' => null, 'title', 'description' => null, 'center' => false])
<div {{ $attributes->merge(['class' => 'mb-8 ' . ($center ? 'mx-auto max-w-2xl text-center' : '')]) }}>
    @if ($eyebrow)<p class="mb-2 text-xs font-bold uppercase tracking-widest text-brand-accent">{{ $eyebrow }}</p>@endif
    <h2 class="font-display text-2xl text-brand-primary sm:text-3xl">{{ $title }}</h2>
    @if ($center)<x-site.ornament-divider class="mt-3" />@else<span class="mt-3 block h-0.5 w-16 bg-brand-gold" aria-hidden="true"></span>@endif
    @if ($description)<p class="mt-3 text-gray-600">{{ $description }}</p>@endif
</div>
