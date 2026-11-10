@props(['data', 'alt' => false, 'first' => false])
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <figure class="container-site max-w-3xl text-center">
        @if (! empty($data['arabic']))<p lang="ar" dir="rtl" class="font-arabic text-3xl leading-loose text-brand-primary">{{ $data['arabic'] }}</p>@endif
        <blockquote class="mt-4 text-lg italic text-gray-700">“{{ $data['translation'] }}”</blockquote>
        @if (! empty($data['source']))<figcaption class="mt-3 text-sm font-semibold text-brand-accent">— {{ $data['source'] }}</figcaption>@endif
        <x-site.ornament-divider class="mt-6" />
    </figure>
</section>
