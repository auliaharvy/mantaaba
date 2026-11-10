<x-site.layout title="Dokumen" description="Profil lembaga, laporan transparansi, pedoman, dan formulir Rumah Mantaaba Sigaling.">
    <x-site.breadcrumb-header title="Dokumen & Laporan" :crumbs="['Dokumen']" subtitle="Profil lembaga, laporan transparansi, pedoman, dan formulir." />
    <div class="container-site py-12">
        @forelse ($documents as $category => $items)
            <section class="mb-10">
                <h2 class="mb-4 text-xl">{{ $category }}</h2>
                <ul class="divide-y divide-brand-border rounded-lg border border-brand-border bg-white">
                    @foreach ($items as $document)
                        <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex gap-3">
                                <x-site.icon name="document" class="h-6 w-6 flex-none text-brand-primary" />
                                <div>
                                    <p class="font-semibold text-brand-heading">{{ $document->title }} @if ($document->visibility === 'anggota')<span class="ml-1 inline-flex items-center gap-1 rounded bg-brand-section-bg px-2 py-0.5 text-xs text-brand-accent"><x-site.icon name="lock" class="h-3 w-3" /> khusus anggota</span>@endif</p>
                                    @if ($document->description)<p class="text-sm text-gray-600">{{ $document->description }}</p>@endif
                                </div>
                            </div>
                            <div class="flex flex-none gap-2 pl-9 sm:pl-0">
                                <x-site.button :href="route('site.documents.download', $document)" size="sm" target="_blank" rel="noopener"><x-site.icon name="download" class="h-4 w-4" /> Unduh</x-site.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <x-site.empty-state title="Belum ada dokumen yang diterbitkan" icon="document" />
        @endforelse
        @guest<p class="text-sm text-gray-600">Beberapa dokumen hanya untuk anggota. <a href="{{ route('login') }}" class="font-semibold text-brand-accent underline">Masuk</a> untuk melihatnya.</p>@endguest
    </div>
</x-site.layout>
