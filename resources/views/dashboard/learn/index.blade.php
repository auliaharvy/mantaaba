<x-dashboard.page title="Belajar" :crumbs="['Belajar']">
    @if ($member->passedAllSkemas())
        <div class="alert alert-success d-flex align-items-center gap-3"><i class="fa fa-award fs-1"></i><div><div class="fw-bolder fs-5">Lulus Inkubasi</div>Selamat! Kelima skema sudah lulus. Pengurus akan mengukuhkan kelulusan Anda.</div></div>
    @endif
    @forelse ($enrollments as $e)
        @php($percent = $e->isLocked() ? 0 : $e->progressPercent())
        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-5">
                <span class="badge badge-{{ $e->status === 'lulus' ? 'success' : ($e->isLocked() ? 'light' : 'primary') }} fs-5 px-4 py-3">{{ $e->skema->code }}</span>
                <div class="flex-grow-1" style="min-width: 0">
                    <div class="fw-bolder fs-5">{{ $e->skema->name }} <span class="text-muted fs-7 fw-normal">{{ $e->skema->month_label }}</span></div>
                    @if ($e->isLocked())
                        <div class="text-muted fs-7"><i class="fa fa-lock"></i> Dibuka mentor sesuai jadwal angkatan</div>
                    @else
                        <div class="d-flex align-items-center gap-3 mt-2"><div class="progress h-8px flex-grow-1"><div class="progress-bar bg-primary" style="width: {{ $percent }}%"></div></div><span class="fs-7">{{ $percent }}%</span></div>
                    @endif
                </div>
                <div class="text-end">
                    @if ($e->result && in_array($e->status, ['lulus', 'belum_lulus']))
                        <div class="fw-bold {{ $e->status === 'lulus' ? 'text-success' : 'text-danger' }}">{{ $e->status === 'lulus' ? '✓ Lulus' : 'Belum lulus' }} ({{ number_format($e->result->final_score, 2, ',', '.') }})</div>
                    @else
                        <div class="text-muted fs-7">{{ App\Models\SkemaEnrollment::STATUSES[$e->status] }}</div>
                    @endif
                    <div class="d-flex gap-2 justify-content-end mt-2">
                        @if ($e->result?->activeCertificate?->isValid())<a href="{{ route('certificate.download', $e->result->activeCertificate) }}" class="btn btn-sm btn-light-success"><i class="fa fa-certificate"></i> Sertifikat</a>@endif
                        @unless ($e->isLocked())<a href="{{ route('learn.skema', $e->skema) }}" class="btn btn-sm btn-primary">{{ $percent > 0 ? 'Lanjutkan' : 'Mulai' }}</a>@endunless
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-muted">Anda belum terdaftar di skema pembelajaran. Skema dibuka saat angkatan Anda berjalan.</div></div>
    @endforelse
</x-dashboard.page>
