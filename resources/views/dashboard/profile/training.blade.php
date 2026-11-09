{{-- Profil -> Pelatihan (PRD 8.11): event diikuti, tiket, sertifikat, total jam PPL tahun ini --}}
<div class="row g-5 mb-5">
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted fs-7">Jam PPL/SKP {{ now()->year }}</div>
            <div class="fs-2hx fw-bolder text-primary">{{ rtrim(rtrim(number_format($pplHours[now()->year] ?? 0, 2, ',', '.'), '0'), ',') }}</div>
            @if ($pplHours->count() > 1 || ($pplHours->isNotEmpty() && ! isset($pplHours[now()->year])))
                <div class="fs-7 text-muted mt-2">
                    @foreach ($pplHours as $year => $hours)@if ($year != now()->year){{ $year }}: {{ rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',') }} jam · @endif @endforeach
                </div>
            @endif
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div><div class="fw-bolder fs-5">Cari pelatihan berikutnya</div><div class="text-muted fs-7">PPL, Brevet, seminar & webinar PD AKP2I Jawa Barat.</div></div>
            <a class="btn btn-sm btn-primary" href="{{ route('site.training') }}" target="_blank">Pusat Pelatihan</a>
        </div></div>
    </div>
</div>
<div class="card">
    <div class="card-header"><div class="card-title fs-4 fw-bolder">Event yang Saya Ikuti</div></div>
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
                        <a class="btn btn-sm btn-light-success" href="{{ Storage::disk('public')->url($p->certificate) }}" target="_blank"><i class="fa fa-download"></i> Sertifikat</a>
                    @endif
                    <a class="btn btn-sm btn-light" href="{{ route('my-training.show', $p) }}">{{ $p->status === 'verified' ? 'Tiket' : 'Detail' }}</a>
                </div>
            </div>
        @empty
            <div class="text-muted">Belum mengikuti event.</div>
        @endforelse
    </div>
</div>
