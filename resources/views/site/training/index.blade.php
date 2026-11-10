<x-site.layout title="Agenda" description="Agenda kegiatan Rumah Mantaaba Sigaling: kajian Tajug, Sedulur Selawase, kelas, open house, dan kegiatan umum.">
    <x-site.breadcrumb-header title="Agenda" :crumbs="['Agenda']" />
    <div class="container-site py-12">
        <nav class="mb-6 flex flex-wrap gap-2" aria-label="Tampilan agenda">
            @foreach (['mendatang' => 'Akan Datang', 'selesai' => 'Selesai', 'kalender' => 'Kalender'] as $key => $label)
                <a href="{{ route('site.training', ['tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif
                    class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $tab === $key ? 'bg-brand-primary text-white' : 'border border-brand-border' }}">{{ $label }}</a>
            @endforeach
        </nav>

        @if ($tab === 'kalender')
            @php
                $start = $month->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
                $end = $month->copy()->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
            @endphp
            <div class="mb-4 flex items-center justify-between">
                <a href="{{ route('site.training', ['tab' => 'kalender', 'bulan' => $month->copy()->subMonth()->format('Y-m')]) }}" class="rounded-site border border-brand-border p-2" aria-label="Bulan sebelumnya"><x-site.icon name="chevron-left" class="h-5 w-5" /></a>
                <h2 class="font-display text-xl text-brand-primary">{{ $month->translatedFormat('F Y') }}</h2>
                <a href="{{ route('site.training', ['tab' => 'kalender', 'bulan' => $month->copy()->addMonth()->format('Y-m')]) }}" class="rounded-site border border-brand-border p-2" aria-label="Bulan berikutnya"><x-site.icon name="chevron-right" class="h-5 w-5" /></a>
            </div>
            {{-- desktop: grid bulan; HP: daftar hari yang berisi agenda --}}
            <div class="hidden grid-cols-7 gap-px overflow-hidden rounded-site border border-brand-border bg-brand-border md:grid">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)<div class="bg-brand-section-bg p-2 text-center text-xs font-bold uppercase text-brand-muted">{{ $day }}</div>@endforeach
                @for ($day = $start->copy(); $day->lte($end); $day->addDay())
                    <div class="min-h-[96px] p-2 text-sm {{ $day->month !== $month->month ? 'bg-gray-50 text-gray-400' : 'bg-white' }} {{ $day->isToday() ? 'ring-2 ring-inset ring-brand-gold' : '' }}">
                        <div class="text-xs font-semibold">{{ $day->day }}</div>
                        @foreach ($byDay[$day->toDateString()] ?? [] as $event)
                            <a href="{{ route('site.training.show', $event->slug) }}" class="mt-1 block truncate rounded bg-brand-primary/10 px-1.5 py-0.5 text-xs text-brand-primary hover:bg-brand-primary hover:text-white" title="{{ $event->title }}">{{ $event->starts_at->format('H:i') }} {{ $event->title }}</a>
                        @endforeach
                    </div>
                @endfor
            </div>
            <div class="space-y-3 md:hidden">
                @forelse ($byDay as $date => $events)
                    <div class="rounded-site border border-brand-border p-4">
                        <p class="text-sm font-bold text-brand-accent">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, j F') }}</p>
                        @foreach ($events as $event)<a href="{{ route('site.training.show', $event->slug) }}" class="mt-1 block font-semibold text-brand-heading">{{ $event->starts_at->format('H:i') }} · {{ $event->title }}</a>@endforeach
                    </div>
                @empty
                    <x-site.empty-state title="Tidak ada agenda bulan ini" icon="calendar" />
                @endforelse
            </div>
        @else
            <form method="GET" class="mb-8 flex flex-wrap gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label class="sr-only" for="jenis">Jenis</label>
                <select id="jenis" name="jenis" class="rounded-md border-brand-border text-sm" onchange="this.form.submit()"><option value="">Semua jenis</option>
                    @foreach (App\Models\Event::TYPES as $v => $l)<option value="{{ $v }}" @selected(request('jenis') === $v)>{{ $l }}</option>@endforeach</select>
            </form>
            @if ($events->isEmpty())
                <x-site.empty-state :title="$tab === 'selesai' ? 'Belum ada agenda selesai' : 'Belum ada agenda mendatang'" icon="calendar" />
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($events as $event)<x-site.card-event :event="$event" />@endforeach</div>
                <div class="mt-10">{{ $events->links() }}</div>
            @endif
        @endif
    </div>
</x-site.layout>
