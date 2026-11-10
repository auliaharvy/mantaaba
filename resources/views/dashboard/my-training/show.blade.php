<x-dashboard.page :title="$event->title" :crumbs="['Agenda Saya', 'Rincian']">
    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card mb-5">
                <div class="card-header">
                    <div class="card-title fs-4 fw-bolder">{{ $event->title }}</div>
                    <div class="card-toolbar"><span class="badge {{ ['verified' => 'badge-light-success', 'pending' => 'badge-light-warning'][$participant->status] ?? 'badge-light-danger' }}">{{ App\Models\Participant::STATUSES[$participant->status] }}</span></div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 fs-6">
                        <dt class="col-sm-4 text-muted">Jadwal</dt><dd class="col-sm-8">{{ $event->starts_at?->translatedFormat('l, j F Y H:i') }} – {{ $event->ends_at?->translatedFormat('H:i') }}</dd>
                        <dt class="col-sm-4 text-muted">Mode</dt><dd class="col-sm-8">{{ App\Models\Event::MODES[$event->mode] }}{{ $event->location ? ' · ' . $event->location : '' }}</dd>
                        <dt class="col-sm-4 text-muted">Biaya</dt><dd class="col-sm-8">Rp{{ number_format($participant->amount, 0, ',', '.') }}</dd>
                        @if ($participant->status === 'rejected' && $participant->notes)<dt class="col-sm-4 text-muted">Alasan ditolak</dt><dd class="col-sm-8">{{ $participant->notes }}</dd>@endif
                        {{-- PRD 8.9 AC: meeting_url hanya untuk peserta terverifikasi --}}
                        @if ($participant->status === 'verified' && $event->meeting_url)
                            <dt class="col-sm-4 text-muted">Tautan acara</dt><dd class="col-sm-8"><a href="{{ $event->meeting_url }}" target="_blank" rel="noopener">{{ $event->meeting_url }}</a></dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if ($participant->status === 'pending')
                <div class="card mb-5">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Pembayaran</div></div>
                    <div class="card-body">
                        <p>Transfer <strong>Rp{{ number_format($participant->amount, 0, ',', '.') }}</strong> ke rekening berikut, lalu unggah bukti transfer.</p>
                        <div class="bg-light rounded p-4 mb-5" style="white-space: pre-line">{{ $bank ?: 'Info rekening belum diisi panitia. Hubungi pengurus.' }}</div>
                        @if ($participant->payment_proof)
                            <div class="alert alert-light-success py-2">Bukti sudah diunggah (<a href="{{ route('participant.proof', $participant) }}" target="_blank">lihat</a>). Menunggu verifikasi panitia; Anda bisa menggantinya bila perlu.</div>
                        @endif
                        <form method="POST" action="{{ route('my-training.proof', $participant) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
                            @csrf
                            <div class="flex-grow-1"><x-dashboard.field name="payment_proof" label="Bukti transfer (gambar/PDF, maks 5 MB)" type="file" accept="image/*,application/pdf" required /></div>
                            <button class="btn btn-sm btn-primary mb-6" type="submit">Unggah</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($participant->certificate)
                <a class="btn btn-sm btn-success" href="{{ route('participant.manual-certificate.download', $participant) }}"><i class="fa fa-download"></i> Unduh sertifikat</a>
            @endif
        </div>
        <div class="col-lg-5">
            @if ($qr)
                <div class="card text-center">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Tiket Check-in</div></div>
                    <div class="card-body">
                        <div class="d-inline-block bg-white p-3 rounded">{!! $qr !!}</div>
                        <div class="fs-2 fw-bolder mt-3 font-monospace" style="letter-spacing: 3px">{{ $participant->ticket_code }}</div>
                        <div class="text-muted fs-7">Tunjukkan QR ini kepada panitia saat hadir.</div>
                        @if ($participant->attended_at)<div class="badge badge-light-success mt-3">Sudah check-in {{ $participant->attended_at->translatedFormat('j M H:i') }}</div>@endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-dashboard.page>
