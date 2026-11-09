<x-site.layout :title="$gallery->title" :description="$gallery->description ?? 'Album ' . $gallery->title"
    :image="($cover = $gallery->coverPath()) ? Storage::disk('public')->url($cover) : null">
    <x-site.breadcrumb-header :title="$gallery->title" :crumbs="['Galeri' => route('site.gallery'), $gallery->title]" />
    <div class="container-site py-12" x-data="{ open: null, photos: @js($gallery->photos->map(fn ($p) => ['src' => Storage::disk('public')->url($p->image), 'caption' => $p->caption])->values()) }"
        @keydown.escape.window="open = null" @keydown.arrow-right.window="if (open !== null) open = (open + 1) % photos.length" @keydown.arrow-left.window="if (open !== null) open = (open - 1 + photos.length) % photos.length">
        <p class="mb-2 text-sm text-gray-600">{{ $gallery->published_at->translatedFormat('l, j F Y') }}</p>
        @if ($gallery->description)<p class="mb-8 max-w-3xl text-gray-700">{{ $gallery->description }}</p>@endif
        @if ($gallery->photos->isEmpty())
            <x-site.empty-state title="Belum ada foto di album ini" icon="photo" />
        @else
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($gallery->photos as $photo)
                    <button type="button" @click="open = {{ $loop->index }}" class="group block overflow-hidden rounded-lg focus-visible:ring-2 focus-visible:ring-brand-primary" aria-label="Perbesar foto {{ $loop->iteration }}{{ $photo->caption ? ': ' . $photo->caption : '' }}">
                        <img src="{{ Storage::disk('public')->url($photo->image) }}" alt="{{ $photo->caption ?? '' }}" class="aspect-square w-full object-cover transition group-hover:scale-105" loading="lazy">
                    </button>
                @endforeach
            </div>
            {{-- lightbox --}}
            <div x-show="open !== null" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-label="Foto" @click.self="open = null">
                <button type="button" @click="open = null" class="absolute right-4 top-4 text-white" aria-label="Tutup"><x-site.icon name="x" class="h-8 w-8" /></button>
                <template x-if="open !== null">
                    <figure class="max-h-full max-w-5xl">
                        <img :src="photos[open].src" :alt="photos[open].caption || ''" class="max-h-[80vh] w-auto rounded">
                        <figcaption class="mt-3 text-center text-white" x-text="photos[open].caption"></figcaption>
                    </figure>
                </template>
                <div class="mt-4 flex gap-4">
                    <button type="button" @click="open = (open - 1 + photos.length) % photos.length" class="rounded-full bg-white/20 p-2 text-white" aria-label="Sebelumnya"><x-site.icon name="chevron-left" class="h-6 w-6" /></button>
                    <button type="button" @click="open = (open + 1) % photos.length" class="rounded-full bg-white/20 p-2 text-white" aria-label="Berikutnya"><x-site.icon name="chevron-right" class="h-6 w-6" /></button>
                </div>
            </div>
        @endif
    </div>
</x-site.layout>
