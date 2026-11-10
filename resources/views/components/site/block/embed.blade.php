@props(['data', 'alt' => false, 'first' => false])
@php($src = App\Support\Blocks::embedUrl($data['url'] ?? null))
@if ($src)
    <section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
        <div class="container-site max-w-4xl">
            @if (! empty($data['title']))<x-site.section-heading :title="$data['title']" center />@endif
            <iframe src="{{ $src }}" title="{{ $data['title'] ?? $data['caption'] ?? 'Konten tersemat' }}" class="w-full rounded-site border-0 {{ str_contains($src, 'youtube') ? 'aspect-video' : 'h-96' }}"
                loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            @if (! empty($data['caption']))<p class="mt-3 text-center text-sm text-gray-600">{{ $data['caption'] }}</p>@endif
        </div>
    </section>
@endif
