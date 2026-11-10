@props(['data', 'alt' => false, 'first' => false])
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        @if (! empty($data['title']))<x-site.section-heading :title="$data['title']" :description="$data['intro'] ?? null" center />@endif
        <x-site.timeline :items="$data['items'] ?? []" />
    </div>
</section>
