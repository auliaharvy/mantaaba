<x-site.layout title="Jurnal" description="Kumpulan jurnal dan publikasi ilmiah perpajakan AKP2I Jawa Barat.">
    <x-site.breadcrumb-header title="Jurnal" :crumbs="['Jurnal']" />
    <div class="container-site py-12">
        <form method="GET" class="mb-8 grid gap-3 sm:grid-cols-4" role="search">
            <label class="sr-only" for="q">Cari</label>
            <input id="q" name="q" value="{{ $q }}" placeholder="Judul, penulis, atau kata kunci" class="rounded-md border-brand-border text-sm sm:col-span-2">
            <label class="sr-only" for="kategori">Kategori</label>
            <select id="kategori" name="kategori" class="rounded-md border-brand-border text-sm">
                <option value="">Semua kategori</option>
                @foreach ($categories as $c)<option @selected(request('kategori') === $c)>{{ $c }}</option>@endforeach
            </select>
            <div class="flex gap-2">
                <label class="sr-only" for="tahun">Tahun</label>
                <select id="tahun" name="tahun" class="flex-1 rounded-md border-brand-border text-sm">
                    <option value="">Semua tahun</option>
                    @foreach ($years as $y)<option @selected((string) request('tahun') === (string) $y)>{{ $y }}</option>@endforeach
                </select>
                <x-site.button size="sm">Cari</x-site.button>
            </div>
        </form>
        @if ($journals->isEmpty())
            <x-site.empty-state title="Jurnal tidak ditemukan" icon="document" />
        @else
            <ul class="space-y-4">
                @foreach ($journals as $journal)
                    <li class="rounded-lg border border-brand-border bg-white p-5">
                        <a href="{{ route('site.journal.show', $journal->slug) }}" class="text-lg font-bold text-brand-heading hover:text-brand-secondary">{{ $journal->title }}</a>
                        <p class="mt-1 text-sm text-gray-600">{{ $journal->authors ?? '-' }} · {{ $journal->date_of_publish->year }}{{ $journal->category ? ' · ' . $journal->category : '' }}</p>
                        @if ($journal->abstract)<p class="mt-2 text-sm text-gray-700">{{ Str::limit($journal->abstract, 220) }}</p>@endif
                    </li>
                @endforeach
            </ul>
            <div class="mt-8">{{ $journals->links() }}</div>
        @endif
        @if ($ejournal)
            <p class="mt-10 text-sm">Lihat juga <a href="{{ $ejournal }}" target="_blank" rel="noopener" class="font-semibold text-brand-secondary underline">eJurnal AKP2I Pusat</a>.</p>
        @endif
    </div>
</x-site.layout>
