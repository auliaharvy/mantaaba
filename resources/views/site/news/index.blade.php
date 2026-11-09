@php($heading = $category?->name ?? ($tag ? '#' . $tag->name : 'Berita & Artikel'))
<x-site.layout :title="$heading" description="Berita, artikel perpajakan, dan kabar kegiatan PD AKP2I Jawa Barat.">
    <x-site.breadcrumb-header :title="$heading" :crumbs="$category || $tag ? ['Berita' => route('site.news'), $heading] : ['Berita']" />
    <div class="container-site grid gap-10 py-12 lg:grid-cols-4">
        <div class="lg:col-span-3">
            @if ($blogs->isEmpty())
                <x-site.empty-state title="Belum ada tulisan" icon="document">{{ $q !== '' ? 'Tidak ada hasil untuk "' . $q . '".' : '' }}</x-site.empty-state>
            @else
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($blogs as $blog)<x-site.card-post :blog="$blog" />@endforeach
                </div>
                <div class="mt-10">{{ $blogs->links() }}</div>
            @endif
        </div>
        <aside class="space-y-8">
            <form method="GET" action="{{ route('site.news') }}" role="search">
                <label for="q" class="mb-2 block font-bold text-brand-heading">Cari</label>
                <div class="flex gap-2">
                    <input id="q" name="q" value="{{ $q }}" class="w-full rounded-md border-brand-border text-sm focus:border-brand-primary focus:ring-brand-primary" placeholder="Judul atau isi">
                    <button class="rounded-md bg-brand-secondary px-3 text-white" aria-label="Cari"><x-site.icon name="search" class="h-4 w-4" /></button>
                </div>
            </form>
            @if ($categories->isNotEmpty())
                <nav aria-label="Kategori">
                    <h2 class="mb-3 text-base">Kategori</h2>
                    <ul class="space-y-1 text-sm">
                        @foreach ($categories as $c)
                            <li><a href="{{ route('site.news.category', $c->slug) }}" class="flex justify-between rounded px-2 py-1 {{ $category?->is($c) ? 'bg-brand-section-bg font-bold text-brand-secondary' : 'hover:bg-brand-section-bg' }}">{{ $c->name }} <span class="text-gray-500">{{ $c->blogs_count }}</span></a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </aside>
    </div>
</x-site.layout>
