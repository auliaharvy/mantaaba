<x-site.layout title="Agenda" description="Agenda kegiatan Rumah Mantaaba Sigaling.">
    <x-site.breadcrumb-header title="Agenda" :crumbs="['Agenda']" />
    <div class="container-site py-12">
        <nav class="mb-6 flex gap-2" aria-label="Waktu">
            @foreach (['mendatang' => 'Akan Datang', 'selesai' => 'Selesai'] as $key => $label)
                <a href="{{ route('site.training', array_merge(request()->except('page'), ['tab' => $key])) }}" @if ($tab === $key) aria-current="page" @endif
                    class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $tab === $key ? 'bg-brand-secondary text-white' : 'border border-brand-border' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <form method="GET" class="mb-8 grid gap-3 sm:grid-cols-4">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="sr-only" for="jenis">Jenis</label>
            <select id="jenis" name="jenis" class="rounded-md border-brand-border text-sm"><option value="">Semua jenis</option>
                @foreach (App\Models\Event::TYPES as $v => $l)<option value="{{ $v }}" @selected(request('jenis') === $v)>{{ $l }}</option>@endforeach</select>
            <label class="sr-only" for="mode">Mode</label>
            <select id="mode" name="mode" class="rounded-md border-brand-border text-sm"><option value="">Semua mode</option>
                @foreach (App\Models\Event::MODES as $v => $l)<option value="{{ $v }}" @selected(request('mode') === $v)>{{ $l }}</option>@endforeach</select>
            <label class="sr-only" for="bulan">Bulan</label>
            <input id="bulan" type="month" name="bulan" value="{{ request('bulan') }}" class="rounded-md border-brand-border text-sm">
            <x-site.button size="sm">Terapkan</x-site.button>
        </form>
        @if ($events->isEmpty())
            <x-site.empty-state :title="$tab === 'selesai' ? 'Belum ada agenda selesai' : 'Belum ada agenda mendatang'" icon="calendar" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($events as $event)<x-site.card-event :event="$event" />@endforeach</div>
            <div class="mt-10">{{ $events->links() }}</div>
        @endif
    </div>
</x-site.layout>
