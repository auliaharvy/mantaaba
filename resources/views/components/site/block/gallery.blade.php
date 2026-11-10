@props(['data', 'alt' => false, 'first' => false])
@php
    $album = ! empty($data['gallery_id']) ? App\Models\Gallery::published()->find($data['gallery_id']) : null;
    $photos = App\Models\GalleryPhoto::with('gallery')
        ->when($album, fn ($q) => $q->where('gallery_id', $album->id), fn ($q) => $q->whereHas('gallery', fn ($g) => $g->published()))
        ->latest('id')->take((int) ($data['limit'] ?? 6))->get();
@endphp
<section class="py-14 {{ $alt ? 'bg-brand-section-bg' : '' }}">
    <div class="container-site">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-site.section-heading :title="$data['title'] ?: ($album->title ?? 'Galeri Kegiatan')" class="mb-0" />
            <a href="{{ $album ? route('site.gallery.show', $album->slug) : route('site.gallery') }}" class="inline-flex items-center gap-1 font-semibold text-brand-accent">Lihat semua <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
        @if ($photos->isEmpty())
            <x-site.empty-state title="Belum ada foto" icon="photo" class="mt-8" />
        @else
            <div class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-3">
                @foreach ($photos as $photo)
                    <a href="{{ route('site.gallery.show', $photo->gallery->slug) }}" class="group block overflow-hidden rounded-site">
                        <img src="{{ Storage::disk('public')->url($photo->image) }}" alt="{{ $photo->caption ?? $photo->gallery->title }}" class="aspect-[4/3] w-full object-cover transition group-hover:scale-105" loading="lazy">
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
