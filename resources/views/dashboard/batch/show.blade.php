@php($tabs = ['pendaftar' => 'Pendaftar', 'siswa' => 'Siswa', 'progres' => 'Progres skema', 'piket' => 'Piket', 'agenda' => 'Agenda'])
<x-dashboard.page :title="$batch->name" :crumbs="['Penerimaan', 'Angkatan', $batch->code]">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-8 align-items-center">
            <div><div class="text-muted fs-7">Kode</div><div class="fw-bolder fs-4">{{ $batch->code }}</div></div>
            <div><div class="text-muted fs-7">Status</div><div class="fw-bolder fs-4">{{ App\Models\Batch::STATUSES[$batch->status] }}</div></div>
            <div><div class="text-muted fs-7">Diterima / kuota</div><div class="fw-bolder fs-4">{{ $counts['diterima'] ?? 0 }} / {{ $batch->quota }}</div></div>
            <div><div class="text-muted fs-7">Pendaftaran</div><div class="fw-bold">{{ $batch->admission_opens_at?->translatedFormat('j M Y') ?? '-' }} – {{ $batch->admission_closes_at?->translatedFormat('j M Y') ?? '-' }}</div></div>
            <div><div class="text-muted fs-7">Inkubasi</div><div class="fw-bold">{{ $batch->starts_at?->translatedFormat('j M Y') ?? '-' }} – {{ $batch->ends_at?->translatedFormat('j M Y') ?? '-' }}</div></div>
            <div class="ms-auto d-flex gap-2">
                @can('update', $batch)<a href="{{ route('batch.edit', $batch) }}" class="btn btn-sm btn-light-success"><i class="fa fa-pen"></i> Ubah</a>@endcan
                <a href="{{ route('batch.index') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i></a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header card-header-stretch overflow-auto">
            <ul class="nav nav-stretch nav-line-tabs border-transparent flex-nowrap fs-6 fw-bold">
                @foreach ($tabs as $key => $label)
                    <li class="nav-item"><a class="nav-link text-nowrap {{ $tab === $key ? 'active' : '' }}" href="?tab={{ $key }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            @switch($tab)
                @case('pendaftar')
                    <div class="d-flex flex-wrap gap-3 mb-5">
                        @foreach (App\Models\Applicant::STATUSES as $status => $label)
                            <a href="{{ route('applicant.index', ['batch' => $batch->id, 'status' => $status]) }}" class="btn btn-sm btn-light">{{ $label }} <span class="badge badge-primary ms-1">{{ $counts[$status] ?? 0 }}</span></a>
                        @endforeach
                    </div>
                    @can('admission-view')<a href="{{ route('applicant.index', ['batch' => $batch->id]) }}" class="btn btn-sm btn-primary">Buka daftar pendaftar</a>@endcan
                    @break
                @case('siswa')
                    @forelse ($students as $student)
                        <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                            <span class="fw-bold">{{ $student->name }}</span><span class="text-muted fs-7">{{ $student->member_number }}</span>
                            <span class="badge badge-light">{{ App\Models\Member::STATUSES[$student->status] }}</span>
                            @can('member-view')<a href="{{ route('member.show', $student) }}" class="ms-auto btn btn-sm btn-light-primary">Detail</a>@endcan
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada siswa diterima.</p>
                    @endforelse
                    @break
                @case('progres')
                    @php($symbols = ['lulus' => '✓', 'berjalan' => '●', 'belum_lulus' => '✕', 'terkunci' => '○'])
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gs-3 gy-3">
                            <thead><tr class="fw-bold bg-light fs-7"><th>Siswa</th>
                                @foreach ($skemas as $skema)
                                    <th class="text-center">{{ $skema->code }}
                                        @can('learning-update')
                                            <form method="POST" action="{{ route('batch.open-skema', [$batch, $skema]) }}" class="d-inline" onsubmit="return confirm('Buka skema {{ $skema->name }} untuk semua siswa angkatan ini?')">@csrf<button class="btn btn-sm btn-link p-0 ms-1 fs-8" title="Buka skema untuk angkatan">buka</button></form>
                                        @endcan
                                    </th>
                                @endforeach
                            </tr></thead>
                            <tbody>
                                @forelse ($students as $student)
                                    @php($byskema = $student->enrollments->keyBy('skema_id'))
                                    <tr><td class="text-nowrap">{{ $student->name }}</td>
                                        @foreach ($skemas as $skema)
                                            @php($e = $byskema[$skema->id] ?? null)
                                            <td class="text-center" title="{{ $e ? App\Models\SkemaEnrollment::STATUSES[$e->status] : 'Belum terdaftar' }}">{{ $e ? $symbols[$e->status] : '-' }}@if ($e?->result?->final_score)<div class="fs-8 text-muted">{{ number_format($e->result->final_score, 1, ',', '') }}</div>@endif</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $skemas->count() + 1 }}" class="text-muted">Belum ada siswa.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted fs-8 mb-0">✓ lulus · ● berjalan · ✕ belum lulus · ○ terkunci. Status angkatan "Berjalan" mendaftarkan siswa ke semua skema (skema pertama langsung dibuka).</p>
                    @break
                @default
                    {{-- piket (M4), agenda (M4) --}}
                    <p class="text-muted mb-0">Data {{ Str::lower($tabs[$tab] ?? '') }} tersedia setelah modul terkait aktif.</p>
            @endswitch
        </div>
    </div>
</x-dashboard.page>
