<x-site.layout :title="$journal->title" :description="$journal->abstract ? Str::limit($journal->abstract, 160) : null" type="article"
    :image="$journal->cover ? Storage::disk('public')->url($journal->cover) : null">
    <x-site.breadcrumb-header :title="$journal->title" :crumbs="['Jurnal' => route('site.journal'), Str::limit($journal->title, 40)]" />
    <div class="container-site py-12">
        <article class="mx-auto max-w-3xl">
            <dl class="mb-8 grid gap-3 text-sm sm:grid-cols-3">
                <div><dt class="text-gray-600">Penulis</dt><dd class="font-semibold">{{ $journal->authors ?? '-' }}</dd></div>
                <div><dt class="text-gray-600">Terbit</dt><dd class="font-semibold">{{ $journal->date_of_publish->translatedFormat('j F Y') }}</dd></div>
                <div><dt class="text-gray-600">Kategori</dt><dd class="font-semibold">{{ $journal->category ?? '-' }}</dd></div>
            </dl>
            <h2 class="mb-3 text-xl">Abstrak</h2>
            <p class="whitespace-pre-line text-gray-800">{{ $journal->abstract ?? 'Abstrak belum tersedia.' }}</p>
            @if ($journal->keywords)<p class="mt-4 text-sm"><strong>Kata kunci:</strong> {{ $journal->keywords }}</p>@endif
            <div class="mt-8 flex flex-wrap items-center gap-4">
                <x-site.button :href="route('site.journal.download', $journal->slug)"><x-site.icon name="download" class="h-4 w-4" /> Unduh PDF</x-site.button>
                <span class="text-sm text-gray-600">{{ number_format($journal->downloads, 0, ',', '.') }}× diunduh</span>
            </div>
            <div class="mt-10 rounded-lg bg-brand-section-bg p-5">
                <h2 class="mb-2 text-base">Sitasi</h2>
                <p class="text-sm text-gray-800">{{ $journal->citation() }}</p>
            </div>
            @if ($ejournal)<p class="mt-6 text-sm">Lihat juga <a href="{{ $ejournal }}" target="_blank" rel="noopener" class="font-semibold text-brand-secondary underline">eJurnal AKP2I Pusat</a>.</p>@endif
        </article>
    </div>
</x-site.layout>
