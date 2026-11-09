@php($url = route('site.news.show', $blog->slug))
<x-site.layout :title="$blog->title" :description="$blog->excerptText()" type="article" :canonical="$url"
    :image="$blog->thumbnail ? Storage::disk('public')->url($blog->thumbnail) : null">
    <x-site.breadcrumb-header :title="$blog->title" :crumbs="['Berita' => route('site.news'), Str::limit($blog->title, 40)]" />
    <div class="container-site py-12">
        <article class="mx-auto max-w-3xl">
            <p class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600">
                @if ($blog->category)<a href="{{ route('site.news.category', $blog->category->slug) }}" class="font-bold uppercase text-brand-secondary">{{ $blog->category->name }}</a>@endif
                <span>{{ $blog->user->name }}</span>
                <time datetime="{{ $blog->published_at->toIso8601String() }}">{{ $blog->published_at->translatedFormat('l, j F Y') }}</time>
                <span>{{ number_format($blog->views, 0, ',', '.') }}× dibaca</span>
            </p>
            @if ($blog->thumbnail)<img src="{{ Storage::disk('public')->url($blog->thumbnail) }}" alt="" class="mb-8 w-full rounded-lg">@endif
            <div class="prose-cms">{!! $blog->body !!}</div>

            @if ($blog->tags->isNotEmpty())
                <div class="mt-8 flex flex-wrap gap-2">
                    @foreach ($blog->tags as $t)<a href="{{ route('site.news.tag', $t->slug) }}" class="rounded-full border border-brand-border px-3 py-1 text-sm hover:border-brand-primary">#{{ $t->name }}</a>@endforeach
                </div>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-brand-border pt-6" x-data="{ copied: false }">
                <span class="text-sm font-bold">Bagikan:</span>
                <a href="https://wa.me/?text={{ rawurlencode($blog->title . ' ' . $url) }}" target="_blank" rel="noopener" class="rounded-md bg-green-700 px-3 py-1.5 text-sm font-semibold text-white">WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}" target="_blank" rel="noopener" class="rounded-md bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white">Facebook</a>
                <a href="https://twitter.com/intent/tweet?url={{ rawurlencode($url) }}&text={{ rawurlencode($blog->title) }}" target="_blank" rel="noopener" class="rounded-md bg-black px-3 py-1.5 text-sm font-semibold text-white">X</a>
                <button type="button" @click="navigator.clipboard.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)" class="rounded-md border border-brand-border px-3 py-1.5 text-sm font-semibold">
                    <span x-show="!copied">Salin tautan</span><span x-show="copied" x-cloak>Tersalin ✓</span>
                </button>
            </div>
        </article>

        @if ($related->isNotEmpty())
            <section class="mx-auto mt-16 max-w-5xl">
                <h2 class="mb-6 text-2xl">Artikel Terkait</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($related as $item)<x-site.card-post :blog="$item" />@endforeach</div>
            </section>
        @endif
    </div>
</x-site.layout>
