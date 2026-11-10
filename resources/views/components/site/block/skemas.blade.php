@props(['data', 'alt' => false, 'first' => false])
@php($skemas = App\Models\Skema::ordered()->where('is_public', true)->get())
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        <x-site.section-heading :title="$data['title'] ?: 'Skema Pembelajaran'" :description="$data['intro'] ?? null" center />
        @if ($skemas->isEmpty())
            <x-site.empty-state title="Rincian skema segera tersedia" icon="academic-cap" />
        @else
            <x-site.skema-list :skemas="$skemas" />
        @endif
    </div>
</section>
