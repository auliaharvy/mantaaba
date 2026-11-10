@props(['data', 'alt' => false, 'first' => false])
@php($tag = $first ? 'h1' : 'h2')
<section class="relative overflow-hidden bg-brand-primary text-white">
    @if (! empty($data['image']))
        <img src="{{ Storage::disk('public')->url($data['image']) }}" alt="" class="absolute inset-0 h-full w-full object-cover" @unless ($first) loading="lazy" @endunless>
        <div class="absolute inset-0 bg-brand-primary-hover" style="opacity: {{ min(80, max(0, (int) ($data['overlay'] ?? 55))) / 100 }}"></div>
    @endif
    <div class="container-site relative flex min-h-[380px] flex-col justify-center py-16 sm:min-h-[480px] sm:py-24">
        <span class="mb-5 block h-0.5 w-20 bg-brand-gold" aria-hidden="true"></span>
        <{{ $tag }} class="max-w-3xl font-display text-3xl leading-tight text-white sm:text-5xl">{{ $data['title'] }}</{{ $tag }}>
        @if (! empty($data['subtitle']))<p class="mt-5 max-w-2xl text-lg text-white/90">{{ $data['subtitle'] }}</p>@endif
        @if (! empty($data['button1_url']) || ! empty($data['button2_url']))
            <div class="mt-8 flex flex-wrap gap-3">
                @if (! empty($data['button1_url']))<x-site.button :href="$data['button1_url']" variant="gold">{{ $data['button1_text'] ?: 'Selengkapnya' }}</x-site.button>@endif
                @if (! empty($data['button2_url']))<x-site.button :href="$data['button2_url']" variant="ghost-light">{{ $data['button2_text'] ?: 'Selengkapnya' }}</x-site.button>@endif
            </div>
        @endif
    </div>
</section>
