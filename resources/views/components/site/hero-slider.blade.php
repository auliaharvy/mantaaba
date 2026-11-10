{{-- Hero slider: gambar + overlay gelap 60%, judul, subjudul, CTA. Isi dari tabel sliders. --}}
@props(['sliders', 'site', 'first' => false])
@php($tag = $first ? 'h1' : 'h2')
@if ($sliders->isEmpty())
    <section class="relative bg-brand-primary text-white">
        <div class="container-site py-20 sm:py-28">
            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-brand-gold">{{ $site->foundation_name }}</p>
            <{{ $tag }} class="max-w-3xl font-display text-3xl text-white sm:text-5xl">{{ $site->site_name }}</{{ $tag }}>
            <p class="mt-4 max-w-2xl text-lg text-white/90">{{ $site->tagline ?? 'Wadah pembinaan karakter & vokasi bisnis pemuda menuju masyarakat Indonesia yang transparan pajak.' }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <x-site.button :href="route('site.admission')" variant="gold">Pendaftaran</x-site.button>
                <x-site.button :href="route('site.training')" variant="ghost-light">Agenda</x-site.button>
            </div>
        </div>
    </section>
@else
    <section x-data="{ i: 0, n: {{ $sliders->count() }}, next() { this.i = (this.i + 1) % this.n }, prev() { this.i = (this.i - 1 + this.n) % this.n } }"
        x-init="if (n > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => next(), 7000)"
        class="relative overflow-hidden bg-brand-primary" aria-roledescription="carousel" aria-label="Sorotan">
        @foreach ($sliders as $slide)
            <div x-show="i === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif x-transition.opacity.duration.500ms
                class="relative min-h-[420px] sm:min-h-[520px]" aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $loop->count }}">
                <img src="{{ Storage::disk('public')->url($slide->image) }}" alt="" class="absolute inset-0 h-full w-full object-cover" @unless ($loop->first) loading="lazy" @endunless>
                <div class="absolute inset-0 bg-brand-primary-hover/60"></div>
                <div class="container-site relative flex min-h-[420px] flex-col justify-center py-16 text-white sm:min-h-[520px]">
                    <{{ $loop->first ? $tag : 'h2' }} class="max-w-3xl font-display text-3xl text-white sm:text-5xl">{{ $slide->title }}</{{ $loop->first ? $tag : 'h2' }}>
                    @if ($slide->subtitle)<p class="mt-4 max-w-2xl text-lg text-white/90">{{ $slide->subtitle }}</p>@endif
                    @if ($slide->button_url)
                        <div class="mt-8"><x-site.button :href="$slide->button_url" variant="gold">{{ $slide->button_text ?: 'Selengkapnya' }}</x-site.button></div>
                    @endif
                </div>
            </div>
        @endforeach
        @if ($sliders->count() > 1)
            <div class="absolute bottom-5 left-0 right-0 flex items-center justify-center gap-3">
                <button type="button" @click="prev()" class="rounded-full bg-white/20 p-2 text-white hover:bg-white/40" aria-label="Slide sebelumnya"><x-site.icon name="chevron-left" class="h-5 w-5" /></button>
                @foreach ($sliders as $slide)
                    <button type="button" @click="i = {{ $loop->index }}" class="h-2.5 rounded-full transition-all" :class="i === {{ $loop->index }} ? 'w-8 bg-white' : 'w-2.5 bg-white/50'" aria-label="Ke slide {{ $loop->iteration }}"></button>
                @endforeach
                <button type="button" @click="next()" class="rounded-full bg-white/20 p-2 text-white hover:bg-white/40" aria-label="Slide berikutnya"><x-site.icon name="chevron-right" class="h-5 w-5" /></button>
            </div>
        @endif
    </section>
@endif
