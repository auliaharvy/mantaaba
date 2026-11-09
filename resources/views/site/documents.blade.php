<x-site.layout title="Peraturan Organisasi" description="AD/ART, Peraturan Organisasi, dan SK AKP2I.">
    <x-site.breadcrumb-header title="Peraturan Organisasi" :crumbs="['Profil' => route('site.profile'), 'Peraturan Organisasi']" />
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
                                    <p class="font-semibold text-brand-heading">{{ $document->title }}</p>
                                    @if ($document->description)<p class="text-sm text-gray-600">{{ $document->description }}</p>@endif
                                </div>
                            </div>
                            <div class="flex flex-none gap-2 pl-9 sm:pl-0">
                                <x-site.button :href="Storage::disk('public')->url($document->file)" variant="outline" size="sm" target="_blank" rel="noopener">Lihat</x-site.button>
                                <x-site.button :href="Storage::disk('public')->url($document->file)" size="sm" download><x-site.icon name="download" class="h-4 w-4" /> Unduh</x-site.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <x-site.empty-state title="Belum ada dokumen yang diterbitkan" icon="document" />
        @endforelse
    </div>
</x-site.layout>
