{{-- Kartu berita (PRD 6.2): thumbnail 16:10, badge kategori, judul, penulis, tanggal. --}}
@props(['blog'])
<article class="group flex flex-col overflow-hidden rounded-lg border border-brand-border bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('site.news.show', $blog->slug) }}" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
        @if ($blog->thumbnail)
            <img src="{{ Storage::disk('public')->url($blog->thumbnail) }}" alt="" class="aspect-[16/10] w-full object-cover transition group-hover:scale-105" loading="lazy">
        @else
            <div class="flex aspect-[16/10] items-center justify-center bg-brand-section-bg"><x-site.icon name="document" class="h-10 w-10 text-brand-muted" /></div>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        @if ($blog->category)
            <a href="{{ route('site.news.category', $blog->category->slug) }}" class="mb-2 self-start rounded bg-brand-section-bg px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-brand-accent">{{ $blog->category->name }}</a>
        @endif
        <h3 class="text-lg"><a href="{{ route('site.news.show', $blog->slug) }}" class="hover:text-brand-accent">{{ $blog->title }}</a></h3>
        <p class="mt-2 flex-1 text-sm text-gray-600">{{ $blog->excerptText(120) }}</p>
        <p class="mt-4 flex items-center gap-2 text-xs text-gray-600">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-section-bg font-bold text-brand-accent" aria-hidden="true">{{ mb_substr($blog->user->name, 0, 1) }}</span>
            {{ $blog->user->name }} · <time datetime="{{ $blog->published_at->toDateString() }}">{{ $blog->published_at->translatedFormat('d M Y') }}</time>
        </p>
    </div>
</article>
