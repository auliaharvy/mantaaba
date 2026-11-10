@props(['data', 'alt' => false, 'first' => false])
@php($cols = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-2 lg:grid-cols-3', '4' => 'sm:grid-cols-2 lg:grid-cols-4'][$data['columns'] ?? '3'] ?? 'sm:grid-cols-2 lg:grid-cols-3')
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        @if (! empty($data['title']))<x-site.section-heading :title="$data['title']" :description="$data['intro'] ?? null" center />@endif
        <div class="grid gap-6 {{ $cols }}">
            @foreach ($data['items'] ?? [] as $item)
                <x-site.pillar-card :title="$item['title']" :icon="$item['icon'] ?? null" :href="$item['url'] ?? null"
                    :image="! empty($item['image']) ? Storage::disk('public')->url($item['image']) : null">{{ $item['text'] ?? '' }}</x-site.pillar-card>
            @endforeach
        </div>
    </div>
</section>
