{{-- Profil -> Agenda: agenda yang diikuti, tiket, sertifikat --}}
<div class="card">
    <div class="card-header">
        <div class="card-title fs-4 fw-bolder">Agenda yang Saya Ikuti</div>
        <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('site.training') }}" target="_blank">Lihat agenda</a></div>
    </div>
    <div class="card-body">
        @forelse ($participations as $p)
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom py-3">
                <div>
                    <a href="{{ route('my-training.show', $p) }}" class="fw-bold">{{ $p->event->title }}</a>
                    <div class="text-muted fs-7">{{ $p->event->starts_at?->translatedFormat('j M Y') }} · {{ App\Models\Participant::STATUSES[$p->status] }}{{ $p->attended_at ? ' · Hadir' : '' }}</div>
                </div>
                <div class="d-flex gap-2">
                    @if ($p->certificateRecord?->isValid() && $p->certificateRecord->file)
                        <a class="btn btn-sm btn-light-success" href="{{ route('certificate.download', $p->certificateRecord) }}"><i class="fa fa-download"></i> Sertifikat</a>
                    @elseif ($p->certificate)
                        <a class="btn btn-sm btn-light-success" href="{{ route('participant.manual-certificate.download', $p) }}"><i class="fa fa-download"></i> Sertifikat</a>
                    @endif
                    <a class="btn btn-sm btn-light" href="{{ route('my-training.show', $p) }}">{{ $p->status === 'verified' ? 'Tiket' : 'Detail' }}</a>
                </div>
            </div>
        @empty
            <div class="text-muted">Belum mengikuti agenda.</div>
        @endforelse
    </div>
</div>
