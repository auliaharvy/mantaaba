@props(['data', 'alt' => false, 'first' => false])
@php($right = ($data['position'] ?? 'left') === 'right')
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site grid items-center gap-10 lg:grid-cols-2">
        @if (! empty($data['image']))
            <img src="{{ Storage::disk('public')->url($data['image']) }}" alt="{{ $data['title'] }}" class="w-full rounded-site object-cover shadow-sm {{ $right ? 'lg:order-2' : '' }}" loading="lazy">
        @endif
        <div class="{{ empty($data['image']) ? 'lg:col-span-2 max-w-3xl' : '' }}">
            <x-site.section-heading :title="$data['title']" class="mb-5" />
            @if (! empty($data['body']))<div class="prose-cms">{!! $data['body'] !!}</div>@endif
            @if (! empty($data['button_url']))<div class="mt-6"><x-site.button :href="$data['button_url']" variant="outline">{{ $data['button_text'] ?: 'Selengkapnya' }}</x-site.button></div>@endif
        </div>
    </div>
</section>
