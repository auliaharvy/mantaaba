@props(['data', 'alt' => false, 'first' => false])
{{-- Data skema & modul dibuat di M3; sampai saat itu tampil ajakan ke halaman kurikulum. --}}
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        <x-site.section-heading :title="$data['title'] ?: 'Skema Pembelajaran'" :description="$data['intro'] ?? null" center />
        <x-site.empty-state title="Rincian skema & modul segera tersedia" icon="academic-cap">
            <a href="{{ route('site.curriculum') }}" class="font-semibold text-brand-accent underline">Lihat halaman kurikulum</a>
        </x-site.empty-state>
    </div>
</section>
