<x-dashboard.page :title="$event->title" :crumbs="['Agenda Saya', 'Rincian']">
    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card mb-5">
                <div class="card-header">
                    <div class="card-title fs-4 fw-bolder">{{ $event->title }}</div>
                    <div class="card-toolbar"><span class="badge {{ ['hadir' => 'badge-light-success', 'terdaftar' => 'badge-light-primary'][$participant->status] ?? 'badge-light-danger' }}">{{ App\Models\Participant::STATUSES[$participant->status] }}</span></div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 fs-6">
                        <dt class="col-sm-4 text-muted">Jadwal</dt><dd class="col-sm-8">{{ $event->starts_at->translatedFormat('l, j F Y H:i') }} – {{ $event->ends_at->translatedFormat('H:i') }}</dd>
                        <dt class="col-sm-4 text-muted">Tempat</dt><dd class="col-sm-8">{{ App\Models\Event::MODES[$event->mode] }}{{ $event->location ? ' · ' . $event->location : '' }}</dd>
                        @if ($participant->status !== 'batal' && $event->meeting_url)
                            <dt class="col-sm-4 text-muted">Tautan online</dt><dd class="col-sm-8"><a href="{{ $event->meeting_url }}" target="_blank" rel="noopener">{{ $event->meeting_url }}</a></dd>
                        @endif
                        @if ($participant->attended_at)<dt class="col-sm-4 text-muted">Hadir</dt><dd class="col-sm-8">{{ $participant->attended_at->translatedFormat('j M Y H:i') }}</dd>@endif
                    </dl>
                </div>
            </div>
            <a href="{{ route('my-training.index') }}" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i> Agenda saya</a>
        </div>
        <div class="col-lg-5">
            @if ($qr)
                <div class="card text-center">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Tiket Check-in</div></div>
                    <div class="card-body">
                        <div class="d-inline-block p-3 bg-white rounded border">{!! $qr !!}</div>
                        <div class="fs-3 fw-bolder mt-3 font-monospace">{{ $participant->ticket_code }}</div>
                        <div class="text-muted fs-7 mt-2">Tunjukkan QR ini atau QR kartu anggota kepada panitia.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-dashboard.page>
