{{-- Halaman CMS (PRD 9.1.3). $content = HTML blok/rich yang sudah dirender (di-cache). --}}
@php($heroFirst = $page->template === 'blocks' && in_array($page->visibleBlocks()[0]['type'] ?? null, ['hero', 'slider'], true))
<x-site.layout :title="$page->slug === 'home' ? $page->meta_title : ($page->meta_title ?: $page->title)"
    :description="$page->meta_description ?: $page->excerpt"
    :image="$page->cover ? Storage::disk('public')->url($page->cover) : null"
    :canonical="$preview ? null : $page->url()" :noindex="$preview">
    @if ($preview)
        <div class="bg-brand-gold/30 py-2 text-center text-sm font-semibold text-brand-heading" role="status">
            Pratinjau{{ $page->is_published ? '' : ' — halaman ini belum terbit' }}. Blok yang disembunyikan tidak tampil.
        </div>
    @endif
    @unless ($heroFirst)
        <x-site.breadcrumb-header :title="$page->title" :crumbs="[$page->title]" :subtitle="$page->excerpt" />
    @endunless
    {!! $content !!}
</x-site.layout>
