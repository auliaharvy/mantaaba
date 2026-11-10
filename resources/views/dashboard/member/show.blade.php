{{-- Detail anggota (PRD 9.4) bertab. --}}
@php
    $tabs = ['profil' => 'Profil', 'perjalanan' => 'Perjalanan', 'belajar' => 'Belajar', 'nilai' => 'Nilai & Sertifikat', 'pembinaan' => 'Pembinaan', 'kesehatan' => 'Kesehatan', 'piket' => 'Piket', 'agenda' => 'Agenda', 'berkas' => 'Berkas'];
    if (! auth()->user()->can('health-view')) unset($tabs['kesehatan']);
    if (! auth()->user()->can('coaching-view')) unset($tabs['pembinaan']);
    $row = fn ($label, $value) => '<dt class="col-sm-4 text-muted fw-normal">' . e($label) . '</dt><dd class="col-sm-8">' . e(filled($value) ? $value : '-') . '</dd>';
    $canUpdate = auth()->user()->can('update', $member);
    $alert = $member->hasViolationAlert();
@endphp
<x-dashboard.page :title="$member->name" :crumbs="['Anggota', 'Data Anggota', $member->member_number]">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-6 align-items-center">
            @if ($member->photoUrl())
                <img src="{{ $member->photoUrl() }}" alt="" class="w-80px h-100px rounded" style="object-fit: cover">
            @else
                <div class="w-80px h-100px rounded bg-light d-flex align-items-center justify-content-center fs-1 fw-bolder text-primary">{{ mb_substr($member->name, 0, 1) }}</div>
            @endif
            <div class="flex-grow-1" style="min-width: 0">
                <div class="fs-2 fw-bolder">{{ $member->name }} @if ($alert)<span class="badge badge-danger ms-2">Batas pelanggaran 30 hari</span>@endif</div>
                <div class="text-muted">{{ $member->member_number }} · {{ App\Models\Member::CATEGORIES[$member->category] }} · <strong>{{ App\Models\Member::STATUSES[$member->status] }}</strong>{{ $member->phase ? ' · Fase ' . App\Models\Member::PHASES[$member->phase] : '' }}</div>
                <div class="text-muted fs-7 mt-1">{{ $member->batch ? 'Angkatan ' . $member->batch->code . ' · ' : '' }}Kamar: {{ $member->room?->code ?? '–' }} · Penempatan: {{ $member->activePlacement?->businessUnit?->name ?? $member->activePlacement?->title ?? '–' }}
                </div>
                @if ($member->enrollments->isNotEmpty())
                    <div class="mt-2">@include('dashboard.learn.badges', ['enrollments' => $member->enrollments->sortBy(fn ($e) => [$e->skema->sort_order, $e->skema->id])])</div>
                @endif
                @if ($member->passedAllSkemas() && $member->phase === 'inkubasi')
                    <div class="alert alert-success py-2 mt-3 mb-0 d-flex flex-wrap align-items-center gap-3"><span><i class="fa fa-award"></i> Lulus Inkubasi — kelima skema lulus.</span>
                        @can('certificate-issue')
                            <form method="POST" action="{{ route('certificate.graduate', $member) }}" onsubmit="return confirm('Kukuhkan kelulusan inkubasi {{ e($member->name) }}? Fase berubah menjadi Magang dan sertifikat kelulusan diterbitkan.')">@csrf<button class="btn btn-sm btn-success">Kukuhkan</button></form>
                        @endcan
                    </div>
                @endif
                @if ($member->category === 'siswa')
                    <div class="fs-8 mt-1 {{ $member->integrity_pact_signed_at ? 'text-success' : 'text-warning' }}">Pakta integritas: {{ $member->integrity_pact_signed_at ? 'disetujui ' . $member->integrity_pact_signed_at->translatedFormat('j M Y') : 'belum disetujui' }}{{ $member->integrity_pact_file ? ' · scan terunggah' : '' }}</div>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($canUpdate)<a href="{{ route('member.edit', $member) }}" class="btn btn-sm btn-light-success"><i class="fa fa-pen"></i> Ubah</a>@endif
                <a href="{{ route('member.card', $member) }}" class="btn btn-sm btn-light"><i class="fa fa-id-card"></i> Kartu</a>
                @if ($member->user && auth()->user()->can('update', $member->user))<a href="{{ route('user.edit', $member->user) }}" class="btn btn-sm btn-light"><i class="fa fa-user-shield"></i> Akun</a>@endif
                <a href="{{ route('member.index') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i></a>
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
                @case('profil')
                    <div class="row">
                        <div class="col-lg-6">
                            <dl class="row">{!! $row('Akun login', $member->user?->email) . $row('Bergabung', $member->joined_at?->translatedFormat('j F Y')) . $row('Keluar', $member->left_at ? $member->left_at->translatedFormat('j F Y') . ' — ' . $member->left_reason : null) . $row('Tampil di website', $member->show_on_site ? 'Ya' : 'Tidak') . $row('Catatan', $member->notes) !!}</dl>
                        </div>
                        <div class="col-lg-6">
                            @if ($private)
                                <dl class="row">{!! $row('NIK', $member->nik) . $row('Tempat, tgl lahir', trim($member->birth_place . ', ' . $member->birth_date?->translatedFormat('j F Y'), ', ')) . $row('Jenis kelamin', App\Models\Applicant::GENDERS[$member->gender] ?? null) . $row('Telepon', $member->phone) . $row('Alamat', collect([$member->address, $member->village, $member->district, $member->regency])->filter()->implode(', ')) . $row('Orang tua / wali', collect([$member->father_name, $member->mother_name, $member->guardian_name])->filter()->implode(' / ')) . $row('WA orang tua', $member->guardian_phone) . $row('Status keluarga', App\Models\Applicant::FAMILY_STATUSES[$member->family_status] ?? null) . $row('Ekonomi', App\Models\Applicant::ECONOMIC_STATUSES[$member->economic_status] ?? null) !!}</dl>
                            @else
                                <p class="text-muted">Data pribadi tersembunyi (perlu hak "Lihat data pribadi").</p>
                            @endif
                        </div>
                    </div>
                    @if ($canUpdate)
                        <form method="POST" action="{{ route('member.status', $member) }}" class="border-top pt-6 mt-4 row g-3 align-items-end">
                            @csrf @method('PATCH')
                            <div class="col-md-3"><x-dashboard.field name="status" label="Ubah status" type="select" :options="App\Models\Member::STATUSES" :value="$member->status" required /></div>
                            <div class="col-md-6"><x-dashboard.field name="reason" label="Alasan" required help="Mengundurkan diri/dikeluarkan menonaktifkan akun. Lulus memindahkan ke kategori alumni." /></div>
                            <div class="col-md-3 mb-6"><button class="btn btn-sm btn-primary w-100" onclick="return confirm('Ubah status anggota?')">Simpan status</button></div>
                        </form>
                    @endif
                    @break

                @case('perjalanan')
                    @forelse ($member->journeys as $journey)
                        <div class="d-flex flex-wrap gap-3 align-items-start py-3 border-bottom">
                            <span class="badge badge-light-primary">{{ App\Models\MemberJourney::TYPES[$journey->type] }}</span>
                            <div class="flex-grow-1">
                                <div class="fw-bold">{{ $journey->title }}{{ $journey->businessUnit ? ' · ' . $journey->businessUnit->name : '' }}{{ $journey->role_level ? ' (' . App\Models\MemberJourney::ROLE_LEVELS[$journey->role_level] . ')' : '' }}</div>
                                <div class="text-muted fs-7">{{ $journey->started_at->translatedFormat('j M Y') }} – {{ $journey->ended_at?->translatedFormat('j M Y') ?? 'sekarang' }}
                                    @if ($journey->meta){{ ' · ' . collect($journey->meta)->map(fn ($v, $k) => strtoupper($k) . ' ' . $v)->implode(', ') }}@endif</div>
                                @if ($journey->notes)<div class="fs-7">{{ $journey->notes }}</div>@endif
                            </div>
                            @if ($canUpdate)
                                @unless ($journey->ended_at)
                                    <form method="POST" action="{{ route('member.journeys.end', [$member, $journey]) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Selesai</button></form>
                                @endunless
                                <form method="POST" action="{{ route('member.journeys.destroy', [$member, $journey]) }}" onsubmit="return confirm('Hapus catatan perjalanan ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-light-danger" title="Hapus"><i class="fa fa-trash"></i></button></form>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">Belum ada catatan perjalanan.</p>
                    @endforelse
                    @if ($canUpdate)
                        <form method="POST" action="{{ route('member.journeys.store', $member) }}" class="row g-3 mt-4 border-top pt-6">
                            @csrf
                            <div class="col-md-3"><x-dashboard.field name="type" label="Jenis" type="select" :options="App\Models\MemberJourney::TYPES" value="penempatan" required /></div>
                            <div class="col-md-5"><x-dashboard.field name="title" label="Judul" required placeholder="Staf admin, Semester 2 UT, Juara ..." /></div>
                            <div class="col-md-4"><x-dashboard.field name="business_unit_id" label="Unit (untuk penempatan)" type="select" :options="$units" placeholder="—" /></div>
                            <div class="col-md-3"><x-dashboard.field name="role_level" label="Level" type="select" :options="App\Models\MemberJourney::ROLE_LEVELS" placeholder="—" /></div>
                            <div class="col-md-3"><x-dashboard.field name="started_at" label="Mulai" type="date" :value="today()" required /></div>
                            <div class="col-md-2"><x-dashboard.field name="meta[nim]" id="meta-nim" label="NIM UT" /></div>
                            <div class="col-md-2"><x-dashboard.field name="meta[semester]" id="meta-semester" label="Semester" type="number" min="1" /></div>
                            <div class="col-md-2"><x-dashboard.field name="meta[ipk]" id="meta-ipk" label="IPK" type="number" step="0.01" min="0" max="4" /></div>
                            <div class="col-md-9"><x-dashboard.field name="notes" label="Catatan" /></div>
                            <div class="col-md-3 mb-6 align-self-end"><button class="btn btn-sm btn-primary w-100">Tambah</button></div>
                        </form>
                        <p class="text-muted fs-8">Penempatan baru otomatis menutup penempatan sebelumnya.</p>
                    @endif
                    @break

                @case('pembinaan')
                    <div class="d-flex flex-wrap gap-2 mb-5">
                        <span class="text-muted me-2">30 hari terakhir:</span>
                        @foreach (App\Models\CoachingNote::TYPES as $type => $label)
                            <span class="badge badge-{{ App\Models\CoachingNote::TYPE_COLORS[$type] }}">{{ $label }}: {{ $summary[$type] ?? 0 }}</span>
                        @endforeach
                        @can('coaching-create')<a href="{{ route('coaching.index', ['member' => $member->id]) }}" class="btn btn-sm btn-light-primary ms-auto"><i class="fa fa-plus"></i> Catat</a>@endcan
                    </div>
                    @forelse ($notes as $note)
                        <div class="d-flex gap-3 py-3 border-bottom">
                            <div class="text-muted fs-7 w-75px flex-shrink-0">{{ $note->date->translatedFormat('j M Y') }}</div>
                            <span class="badge badge-{{ App\Models\CoachingNote::TYPE_COLORS[$note->type] }} align-self-start">{{ App\Models\CoachingNote::TYPES[$note->type] }}</span>
                            <div class="flex-grow-1"><span class="text-muted fs-7">{{ App\Models\CoachingNote::DIMENSIONS[$note->dimension] }}</span><div>{{ $note->body }}</div>
                                @if ($note->follow_up)<div class="fs-7 text-muted">Tindak lanjut: {{ $note->follow_up }}</div>@endif</div>
                            <div class="text-muted fs-7 text-end">{{ $note->author?->name ?? 'Sistem' }}<br>{{ $note->visibility === 'anggota' ? 'terlihat anggota' : 'privat' }}</div>
                        </div>
                    @empty
                        <p class="text-muted">Belum ada catatan pembinaan.</p>
                    @endforelse
                    @break

                @case('kesehatan')
                    @can('health-create')<a href="{{ route('health.create', ['member' => $member->id]) }}" class="btn btn-sm btn-light-primary mb-4"><i class="fa fa-plus"></i> Catat kesehatan</a>@endcan
                    @forelse ($health as $record)
                        <div class="d-flex flex-wrap gap-3 py-3 border-bottom">
                            <div class="text-muted fs-7 w-75px">{{ $record->date->translatedFormat('j M Y') }}</div>
                            <div class="flex-grow-1"><div class="fw-bold">{{ $record->complaint }}</div><div class="fs-7 text-muted">{{ App\Models\HealthRecord::ACTIONS[$record->action] }}{{ $record->facility ? ' · ' . $record->facility : '' }}{{ $record->note ? ' · ' . $record->note : '' }}</div></div>
                            <div class="fs-7">{{ $record->cost > 0 ? 'Rp ' . number_format($record->cost, 0, ',', '.') : '' }}</div>
                        </div>
                    @empty
                        <p class="text-muted">Belum ada catatan kesehatan.</p>
                    @endforelse
                    @break

                @case('agenda')
                    @forelse ($participations as $p)
                        <div class="d-flex gap-3 py-2 border-bottom"><span class="fw-bold">{{ $p->event->title }}</span><span class="text-muted fs-7">{{ $p->event->starts_at?->translatedFormat('j M Y') }}</span><span class="badge badge-light ms-auto">{{ $p->attended_at ? 'Hadir' : ucfirst($p->status) }}</span></div>
                    @empty
                        <p class="text-muted">Belum mengikuti agenda.</p>
                    @endforelse
                    @break

                @case('berkas')
                    @if ($private)
                        <div class="d-flex flex-wrap gap-3 mb-6">
                            @foreach (App\Http\Controllers\MemberController::DOCUMENT_TYPES as $key => $label)
                                @if (isset($member->documents[$key]))<a href="{{ route('member.document', [$member, $key]) }}" target="_blank" class="btn btn-sm btn-light"><i class="fa fa-paperclip"></i> {{ $label }}</a>@endif
                            @endforeach
                            @if ($member->integrity_pact_file)<a href="{{ route('member.document', [$member, 'pakta']) }}" target="_blank" class="btn btn-sm btn-light"><i class="fa fa-file-signature"></i> Pakta integritas (scan)</a>@endif
                            @if (! $member->documents && ! $member->integrity_pact_file)<span class="text-muted">Belum ada berkas.</span>@endif
                        </div>
                        @if ($canUpdate)
                            <form method="POST" action="{{ route('member.documents.store', $member) }}" enctype="multipart/form-data" class="row g-3 border-top pt-6">
                                @csrf
                                <div class="col-md-4"><x-dashboard.field name="key" label="Jenis berkas" type="select" :options="App\Http\Controllers\MemberController::DOCUMENT_TYPES + ['pakta' => 'Scan pakta integritas']" required /></div>
                                <div class="col-md-5"><x-dashboard.field name="file" label="File (jpg/png/pdf, maks 4 MB)" type="file" accept=".jpg,.jpeg,.png,.pdf" required /></div>
                                <div class="col-md-3 mb-6 align-self-end"><button class="btn btn-sm btn-primary w-100">Unggah</button></div>
                            </form>
                        @endif
                    @else
                        <p class="text-muted">Berkas pribadi hanya untuk pengurus dengan hak "Lihat data pribadi".</p>
                    @endif
                    @break

                @case('belajar')
                    @forelse ($member->enrollments->sortBy(fn ($e) => [$e->skema->sort_order, $e->skema->id]) as $e)
                        <div class="d-flex flex-wrap align-items-center gap-3 py-3 border-bottom">
                            <span class="badge badge-light-primary">{{ $e->skema->code }}</span><span class="fw-bold flex-grow-1">{{ $e->skema->name }}</span>
                            <span class="fs-7 text-muted">{{ App\Models\SkemaEnrollment::STATUSES[$e->status] }}{{ $e->isLocked() ? '' : ' · ' . $e->progressPercent() . '% materi' }}</span>
                        </div>
                    @empty
                        <p class="text-muted">Belum terdaftar di skema.</p>
                    @endforelse
                    @if ($progress->isNotEmpty())
                        <h4 class="fs-6 fw-bolder mt-6 mb-3">Tugas dikirim</h4>
                        @foreach ($progress as $p)
                            <div class="d-flex gap-3 py-2 border-bottom fs-7"><span class="text-muted w-75px">{{ $p->submitted_at->translatedFormat('j M') }}</span><span class="flex-grow-1">{{ $p->lesson->module->skema->code }} · {{ $p->lesson->title }}</span>
                                <span>{{ $p->reviewed_at ? 'Nilai ' . $p->score : 'Belum dinilai' }}</span>@can('learning-view')<a href="{{ route('submission.show', $p) }}">Buka</a>@endcan</div>
                        @endforeach
                    @endif
                    @break

                @case('nilai')
                    <div class="table-responsive">
                        <table class="table table-row-bordered gs-3 gy-3 fs-7">
                            <thead><tr class="fw-bold bg-light"><th>Skema</th><th>K</th><th>S</th><th>C</th><th>SC</th><th>Akhir</th><th>Hasil</th><th>Sertifikat</th></tr></thead>
                            <tbody>
                                @foreach ($member->enrollments->sortBy(fn ($e) => [$e->skema->sort_order, $e->skema->id]) as $e)
                                    @php $r = $e->result; @endphp
                                    <tr><td>{{ $e->skema->code }}</td>
                                        @foreach (['knowledge', 'skill', 'character', 'social_cohesion', 'final_score'] as $f)<td>{{ $r?->{$f} !== null ? number_format($r->{$f}, 2, ',', '.') : '-' }}</td>@endforeach
                                        <td>{{ $r ? ($r->passed ? 'Lulus · ' . $r->predicate : 'Belum lulus') : '-' }}</td>
                                        <td>@if ($c = $r?->activeCertificate){{ $c->number }}@else - @endif</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @foreach ($member->certificates as $c)
                        <div class="d-flex flex-wrap gap-3 align-items-center py-2 border-bottom fs-7">
                            <span class="fw-bold">{{ $c->number }}</span><span class="flex-grow-1">{{ $c->title() }}</span>
                            <span class="badge {{ $c->revoked_at ? 'badge-light-danger' : ($c->issued_at ? 'badge-light-success' : 'badge-light') }}">{{ $c->revoked_at ? 'Dicabut: ' . $c->revoke_reason : ($c->issued_at ? 'Valid' : 'Diproses') }}</span>
                            @include('dashboard.certificate.action', ['certificate' => $c])
                        </div>
                    @endforeach
                    @break

                @case('piket')
                    <p class="text-muted">Bulan ini: <strong>{{ $dutyRate !== null ? $dutyRate . '% selesai' : 'belum ada piket yang lewat' }}</strong>. @can('dorm-view')<a href="{{ route('duty.report') }}">Laporan piket</a>@endcan</p>
                    @forelse ($duties as $duty)
                        <div class="d-flex gap-3 py-2 border-bottom fs-7"><span class="text-muted w-100px">{{ $duty->date->translatedFormat('D, j M') }}</span><span class="flex-grow-1">{{ $duty->area->name }} · {{ App\Models\Duty::SHIFTS[$duty->shift] }}</span>
                            <span class="badge badge-light{{ $duty->status === 'terlewat' ? '-danger' : (in_array($duty->status, App\Models\Duty::DONE) ? '-success' : '') }}">{{ App\Models\Duty::STATUSES[$duty->status] }}</span></div>
                    @empty
                        <p class="text-muted">Belum ada jadwal piket.</p>
                    @endforelse
                    @break

                @default
                    <p class="text-muted mb-0">Data {{ Str::lower($tabs[$tab] ?? '') }} tersedia setelah modul terkait aktif.</p>
            @endswitch
        </div>
    </div>
</x-dashboard.page>
