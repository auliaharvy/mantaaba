@props(['data', 'alt' => false, 'first' => false])
@php
    $items = collect($data['items'] ?? [])
        ->map(fn ($item) => $item + ['shown' => ! empty($item['auto']) ? (App\Support\SiteStats::value($item['auto']) ?? $item['value'] ?? null) : ($item['value'] ?? null)])
        ->filter(fn ($item) => filled($item['shown']));
@endphp
@if ($items->isNotEmpty())
    <section class="py-12 {{ $alt ? 'bg-brand-section-bg' : '' }}">
        <div class="container-site">
            @if (! empty($data['title']))<x-site.section-heading :title="$data['title']" center />@endif
            <div class="grid grid-cols-2 gap-4 {{ $items->count() >= 4 ? 'lg:grid-cols-4' : ($items->count() === 3 ? 'lg:grid-cols-3' : '') }}">
                @foreach ($items as $item)<x-site.stat :value="$item['shown']" :label="$item['label']" />@endforeach
            </div>
        </div>
    </section>
@endif
