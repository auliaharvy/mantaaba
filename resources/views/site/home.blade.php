@php($site = App\Models\Setting::current())
<x-site.layout>
    <x-site.hero-slider :sliders="$sliders" :site="$site" />

    <section class="relative z-10 -mt-10">
        <div class="container-site"><x-site.stat-counter :stats="$stats" /></div>
    </section>

    @if ($welcome)
        <section class="py-16">
            <div class="container-site grid items-center gap-10 lg:grid-cols-5">
                @if ($welcome->cover)
                    <img src="{{ Storage::disk('public')->url($welcome->cover) }}" alt="{{ $welcome->title }}" class="w-full rounded-lg object-cover lg:col-span-2" loading="lazy">
                @endif
                <div class="{{ $welcome->cover ? 'lg:col-span-3' : 'lg:col-span-5 max-w-3xl' }}">
                    <x-site.section-heading eyebrow="Sambutan" :title="$welcome->title" />
                    <div class="prose-cms">{!! $welcome->excerpt ? e($welcome->excerpt) : Str::limit(strip_tags($welcome->body), 600) !!}</div>
                    <div class="mt-6"><x-site.button :href="route('site.profile')" variant="outline">Profil Lengkap</x-site.button></div>
                </div>
            </div>
        </section>
    @endif

    <section class="bg-brand-section-bg py-16">
        <div class="container-site">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-site.section-heading eyebrow="Kabar Terbaru" title="Berita & Artikel" class="mb-0" />
                <a href="{{ route('site.news') }}" class="inline-flex items-center gap-1 font-semibold text-brand-secondary">Semua berita <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
            @if ($news->isEmpty())
                <x-site.empty-state title="Belum ada berita" icon="document" class="mt-8" />
            @else
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($news as $blog)<x-site.card-post :blog="$blog" />@endforeach</div>
            @endif
        </div>
    </section>

    <section class="py-16">
        <div class="container-site">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-site.section-heading eyebrow="Agenda" title="Kegiatan Mendatang" description="Kajian, open house, dan kegiatan Rumah Mantaaba Sigaling." class="mb-0" />
                <a href="{{ route('site.training') }}" class="inline-flex items-center gap-1 font-semibold text-brand-secondary">Semua agenda <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
            @if ($events->isEmpty())
                <x-site.empty-state title="Belum ada agenda" icon="calendar" class="mt-8" />
            @else
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($events as $event)<x-site.card-event :event="$event" />@endforeach</div>
            @endif
        </div>
    </section>

    <section class="bg-brand-section-bg py-16">
        <div class="container-site">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-site.section-heading eyebrow="Dokumentasi" title="Galeri Kegiatan" class="mb-0" />
                <a href="{{ route('site.gallery') }}" class="inline-flex items-center gap-1 font-semibold text-brand-secondary">Semua album <x-site.icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
            @if ($photos->isEmpty())
                <x-site.empty-state title="Belum ada foto" icon="photo" class="mt-8" />
            @else
                <div class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-3">
                    @foreach ($photos as $photo)
                        <a href="{{ route('site.gallery.show', $photo->gallery->slug) }}" class="group block overflow-hidden rounded-lg">
                            <img src="{{ Storage::disk('public')->url($photo->image) }}" alt="{{ $photo->caption ?? $photo->gallery->title }}" class="aspect-[4/3] w-full object-cover transition group-hover:scale-105" loading="lazy">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="py-16">
        <div class="container-site">
            <div class="rounded-2xl bg-brand-secondary px-6 py-12 text-center text-white sm:px-12">
                <h2 class="text-2xl text-white sm:text-3xl">Program Pendidikan Gratis untuk Pemuda</h2>
                <p class="mx-auto mt-3 max-w-2xl text-white/90">Inkubasi karakter & vokasi bisnis 6 bulan, berasrama, seluruh biaya ditanggung. Penerimaan dibuka per angkatan.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <x-site.button :href="route('site.contact')" variant="light">Tanya Pendaftaran</x-site.button>
                    <x-site.button :href="route('site.profile')" variant="ghost-light">Tentang Program</x-site.button>
                </div>
            </div>
        </div>
    </section>
</x-site.layout>
