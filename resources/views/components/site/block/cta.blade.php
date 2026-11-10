@props(['data', 'alt' => false, 'first' => false])
@php
    $style = $data['style'] ?? 'primer';
    $box = ['primer' => 'bg-brand-primary text-white', 'aksen' => 'bg-brand-accent text-white', 'krem' => 'bg-brand-section-bg text-brand-heading border border-brand-border'][$style] ?? 'bg-brand-primary text-white';
    $light = $style !== 'krem';
@endphp
<section class="py-14">
    <div class="container-site">
        <div class="rounded-2xl px-6 py-12 text-center sm:px-12 {{ $box }}">
            <x-site.ornament-divider class="mb-4" />
            <h2 class="font-display text-2xl sm:text-3xl {{ $light ? 'text-white' : 'text-brand-primary' }}">{{ $data['title'] }}</h2>
            @if (! empty($data['text']))<p class="mx-auto mt-3 max-w-2xl {{ $light ? 'text-white/90' : 'text-gray-700' }}">{{ $data['text'] }}</p>@endif
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @if (! empty($data['button1_url']))<x-site.button :href="$data['button1_url']" :variant="$light ? 'gold' : 'primary'">{{ $data['button1_text'] ?: 'Selengkapnya' }}</x-site.button>@endif
                @if (! empty($data['button2_url']))<x-site.button :href="$data['button2_url']" :variant="$light ? 'ghost-light' : 'outline'">{{ $data['button2_text'] ?: 'Selengkapnya' }}</x-site.button>@endif
            </div>
        </div>
    </div>
</section>
