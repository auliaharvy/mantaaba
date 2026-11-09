<x-site.layout title="Galeri" description="Dokumentasi kegiatan Rumah Mantaaba Sigaling.">
    <x-site.breadcrumb-header title="Galeri" :crumbs="['Profil' => route('site.profile'), 'Galeri']" />
    <div class="container-site py-12">
        @if ($galleries->isEmpty())
            <x-site.empty-state title="Belum ada album" icon="photo" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($galleries as $gallery)
                    <a href="{{ route('site.gallery.show', $gallery->slug) }}" class="group overflow-hidden rounded-lg border border-brand-border bg-white shadow-sm">
                        @if ($cover = $gallery->coverPath())
                            <img src="{{ Storage::disk('public')->url($cover) }}" alt="" class="aspect-[16/10] w-full object-cover transition group-hover:scale-105" loading="lazy">
                        @else
                            <div class="flex aspect-[16/10] items-center justify-center bg-brand-section-bg"><x-site.icon name="photo" class="h-10 w-10 text-brand-muted" /></div>
                        @endif
                        <div class="p-5">
                            <h2 class="text-lg group-hover:text-brand-secondary">{{ $gallery->title }}</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ $gallery->published_at->translatedFormat('d M Y') }} · {{ $gallery->photos_count }} foto</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-10">{{ $galleries->links() }}</div>
        @endif
    </div>
</x-site.layout>
