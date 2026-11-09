<x-dashboard.page title="Pelatihan Saya" :crumbs="['Pelatihan Saya']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Event yang Saya Ikuti</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('site.training') }}" target="_blank">Cari pelatihan</a></div>
        </div>
        <div class="card-body">
            @forelse ($participations as $p)
                <a href="{{ route('my-training.show', $p) }}" class="d-flex flex-wrap justify-content-between gap-3 border-bottom py-4 text-gray-800">
                    <span><span class="fw-bold d-block">{{ $p->event->title }}</span>
                        <span class="text-muted fs-7">{{ $p->event->starts_at?->translatedFormat('l, j F Y H:i') }}</span></span>
                    <span class="badge {{ ['verified' => 'badge-light-success', 'pending' => 'badge-light-warning'][$p->status] ?? 'badge-light-danger' }} align-self-center">{{ App\Models\Participant::STATUSES[$p->status] }}</span>
                </a>
            @empty
                <div class="text-muted">Belum ada pendaftaran. <a href="{{ route('site.training') }}">Lihat jadwal pelatihan</a>.</div>
            @endforelse
        </div>
    </div>
</x-dashboard.page>
