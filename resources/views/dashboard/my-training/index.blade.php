<x-dashboard.page title="Agenda Saya" :crumbs="['Agenda Saya']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Agenda yang Saya Ikuti</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('site.training') }}" target="_blank">Lihat semua agenda</a></div>
        </div>
        <div class="card-body">
            @forelse ($participations as $p)
                <a href="{{ route('my-training.show', $p) }}" class="d-flex flex-wrap justify-content-between gap-3 border-bottom py-4 text-gray-800">
                    <span><span class="fw-bold d-block">{{ $p->event->title }}</span>
                        <span class="text-muted fs-7">{{ $p->event->starts_at->translatedFormat('l, j F Y H:i') }}</span></span>
                    <span class="badge {{ ['hadir' => 'badge-light-success', 'terdaftar' => 'badge-light-primary'][$p->status] ?? 'badge-light-danger' }} align-self-center">{{ App\Models\Participant::STATUSES[$p->status] }}</span>
                </a>
            @empty
                <div class="text-muted">Belum ada agenda. <a href="{{ route('site.training') }}">Lihat jadwal agenda</a>.</div>
            @endforelse
        </div>
    </div>
</x-dashboard.page>
