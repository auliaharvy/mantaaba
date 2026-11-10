{{-- Kartu agenda (PRD 9.8): poster, jenis, tanggal, kuota tersisa, tombol Daftar. --}}
@props(['event'])
@php($left = $event->seatsLeft())
<article class="flex flex-col overflow-hidden rounded-site border border-brand-border bg-white shadow-sm">
    <a href="{{ route('site.training.show', $event->slug) }}" tabindex="-1" aria-hidden="true">
        @if ($event->thumbnail)
            <img src="{{ Storage::disk('public')->url($event->thumbnail) }}" alt="" class="aspect-[16/10] w-full object-cover" loading="lazy">
        @else
            <div class="flex aspect-[16/10] items-center justify-center bg-brand-section-bg"><x-site.icon name="calendar" class="h-10 w-10 text-brand-muted" /></div>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <div class="mb-2 flex flex-wrap gap-2 text-xs font-bold uppercase">
            <span class="rounded bg-brand-section-bg px-2 py-0.5 text-brand-accent">{{ App\Models\Event::TYPES[$event->type] }}</span>
            <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">{{ App\Models\Event::MODES[$event->mode] }}</span>
        </div>
        <h3 class="text-lg"><a href="{{ route('site.training.show', $event->slug) }}" class="hover:text-brand-accent">{{ $event->title }}</a></h3>
        <p class="mt-2 flex items-center gap-2 text-sm text-gray-700"><x-site.icon name="calendar" class="h-4 w-4 text-brand-primary" />
            <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->starts_at->translatedFormat('l, j M Y · H:i') }}</time></p>
        <p class="mt-1 text-sm text-gray-600">
            @if ($event->status === 'done' || $event->ends_at->isPast()) Selesai
            @elseif ($event->audience !== 'publik') Agenda internal anggota
            @elseif ($left === null) Kuota tidak terbatas
            @elseif ($left === 0) <span class="font-bold text-red-700">Kuota penuh</span>
            @else Sisa kuota {{ $left }} @endif
        </p>
        <div class="mt-auto pt-4">
            <x-site.button :href="route('site.training.show', $event->slug)" size="sm" :variant="$event->registrationOpen() && $left !== 0 ? 'primary' : 'outline'">
                {{ $event->registrationOpen() && $left !== 0 ? 'Daftar' : 'Detail' }}</x-site.button>
        </div>
    </div>
</article>
