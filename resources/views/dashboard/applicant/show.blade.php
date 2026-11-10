@php
    $a = $applicant;
    $row = fn ($label, $value) => '<dt class="col-sm-4 text-muted fw-normal">' . e($label) . '</dt><dd class="col-sm-8">' . e($value ?: '-') . '</dd>';
@endphp
<x-dashboard.page :title="$a->name" :crumbs="['Penerimaan', 'Pendaftar', $a->registration_number]">
    @if (session('wa_link') || session('password_link'))
        <div class="alert alert-light-success d-flex flex-wrap align-items-center gap-3">
            <span>Kabari pendaftar lewat WhatsApp{{ session('password_link') ? ' (berisi akun & tautan atur password, berlaku 60 menit)' : '' }}.</span>
            @if (session('wa_link'))<a href="{{ session('wa_link') }}" target="_blank" rel="noopener" class="btn btn-sm btn-success"><i class="fab fa-whatsapp"></i> Kirim WhatsApp</a>@endif
        </div>
    @endif
    <div class="row g-5">
        <div class="col-xl-8">
            <div class="card mb-5">
                <div class="card-header">
                    <div class="card-title flex-column align-items-start">
                        <span class="fs-3 fw-bolder">{{ $a->name }} @if ($a->is_priority)<span class="badge badge-light-warning ms-2">★ Prioritas</span>@endif</span>
                        <span class="text-muted fs-7">{{ $a->registration_number }} · {{ $a->batch->name }} · {{ App\Models\Applicant::STATUSES[$a->status] }}</span>
                    </div>
                    <div class="card-toolbar gap-2">
                        @if ($wa = App\Models\Setting::waLink($a->phone))<a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-light-success"><i class="fab fa-whatsapp"></i> Pendaftar</a>@endif
                        @if ($wa = App\Models\Setting::waLink($a->guardian_phone))<a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-sm btn-light-success"><i class="fab fa-whatsapp"></i> Orang tua</a>@endif
                        <a href="{{ route('applicant.index', ['batch' => $a->batch_id, 'status' => $a->status]) }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <h4 class="fs-6 fw-bolder mb-3">Data diri</h4>
                    <dl class="row mb-6">{!! $row('NIK', $a->nik) . $row('Tempat, tanggal lahir', $a->birth_place . ', ' . $a->birth_date->translatedFormat('j F Y') . ' (' . $a->age() . ' th)') . $row('Jenis kelamin', App\Models\Applicant::GENDERS[$a->gender]) . $row('WhatsApp', $a->phone) . $row('Email', $a->email) . $row('Alamat', "{$a->address}, {$a->village}, {$a->district}, {$a->regency}") !!}</dl>
                    <h4 class="fs-6 fw-bolder mb-3">Pendidikan & keluarga</h4>
                    <dl class="row mb-6">{!! $row('Pendidikan', trim($a->education . ' ' . $a->school_name . ' ' . $a->graduation_year)) . $row('Ayah / Ibu / Wali', collect([$a->father_name, $a->mother_name, $a->guardian_name])->filter()->implode(' / ')) . $row('Pekerjaan orang tua', $a->parent_job) . $row('WA orang tua/wali', $a->guardian_phone) . $row('Status keluarga', App\Models\Applicant::FAMILY_STATUSES[$a->family_status]) . $row('Ekonomi', App\Models\Applicant::ECONOMIC_STATUSES[$a->economic_status]) . $row('Jumlah saudara', $a->siblings) !!}</dl>
                    <h4 class="fs-6 fw-bolder mb-3">Motivasi</h4>
                    <p style="white-space: pre-line">{{ $a->motivation }}</p>
                    <dl class="row mb-6">{!! $row('Sumber informasi', $a->info_source) . $row('Riwayat kesehatan', $a->health_history) !!}</dl>
                    <h4 class="fs-6 fw-bolder mb-3">Berkas</h4>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach (App\Models\Applicant::DOCUMENTS as $key => [$label])
                            @if (isset($a->documents[$key]))
                                <a href="{{ route('applicant.document', [$a, $key]) }}" target="_blank" class="btn btn-sm btn-light"><i class="fa fa-paperclip"></i> {{ $label }}</a>
                            @endif
                        @endforeach
                    </div>
                    @if (isset($a->documents['photo']) && ! str_ends_with($a->documents['photo'], '.pdf'))
                        <img src="{{ route('applicant.document', [$a, 'photo']) }}" alt="Foto {{ $a->name }}" class="mt-4 h-150px rounded">
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('applicant.scores', $a) }}" class="card">
                @csrf @method('PATCH')
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Penilaian (1–5) · rata-rata {{ $a->averageScore() ?? '-' }}</div></div>
                <div class="card-body row">
                    @foreach (App\Models\Applicant::SCORES as $key => $label)
                        <div class="col-6 col-md mb-4">
                            <label class="form-label fs-7" for="score-{{ $key }}">{{ $label }}</label>
                            <select id="score-{{ $key }}" name="scores[{{ $key }}]" class="form-select form-select-sm" @cannot('update', $a) disabled @endcannot>
                                <option value="">-</option>@for ($i = 1; $i <= 5; $i++)<option @selected(($a->scores[$key] ?? null) == $i)>{{ $i }}</option>@endfor
                            </select>
                        </div>
                    @endforeach
                    <div class="col-md-6"><x-dashboard.field name="interview_note" :label="'Catatan wawancara' . ($a->interview_at ? ' (' . $a->interview_at->translatedFormat('j M Y H:i') . ')' : '')" type="textarea" :value="$a->interview_note" /></div>
                    <div class="col-md-6"><x-dashboard.field name="home_visit_note" :label="'Catatan kunjungan rumah' . ($a->home_visit_at ? ' (' . $a->home_visit_at->translatedFormat('j M Y H:i') . ')' : '')" type="textarea" :value="$a->home_visit_note" /></div>
                </div>
                @can('update', $a)<div class="card-footer text-end"><button class="btn btn-sm btn-primary">Simpan penilaian</button></div>@endcan
            </form>
        </div>

        <div class="col-xl-4">
            @if ($a->member)
                <div class="card mb-5"><div class="card-body">Diterima sebagai <strong>{{ $a->member->member_number }}</strong>.
                    @can('member-view')<a href="{{ route('member.show', $a->member) }}">Buka data anggota</a>@endcan</div></div>
            @endif
            @unless ($a->isFinal())
                @if ($a->nextStages())
                    @can('update', $a)
                        <form method="POST" action="{{ route('applicant.move', $a) }}" class="card mb-5">
                            @csrf
                            <div class="card-header"><div class="card-title fs-5 fw-bolder">Pindah tahap</div></div>
                            <div class="card-body">
                                <x-dashboard.field name="status" label="Tahap" type="select" :options="collect($a->nextStages())->mapWithKeys(fn ($s) => [$s => App\Models\Applicant::STATUSES[$s]])->all()" required />
                                <x-dashboard.field name="scheduled_at" label="Jadwal wawancara/kunjungan" type="datetime-local" />
                                <x-dashboard.field name="note" label="Catatan" type="textarea" required />
                            </div>
                            <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Simpan</button></div>
                        </form>
                    @endcan
                @endif
                @can('decide', $a)
                    <div class="card mb-5">
                        <div class="card-header"><div class="card-title fs-5 fw-bolder">Keputusan</div></div>
                        <div class="card-body">
                            @if ($a->canBeAccepted())
                                <form method="POST" action="{{ route('applicant.accept', $a) }}" class="mb-6" onsubmit="return confirm('Terima {{ e($a->name) }} sebagai siswa? Akun anggota akan dibuat.')">
                                    @csrf
                                    <x-dashboard.field name="note" id="accept-note" label="Catatan penerimaan" type="textarea" />
                                    <button class="btn btn-sm btn-success w-100"><i class="fa fa-check"></i> Terima</button>
                                </form>
                            @else
                                <p class="text-muted fs-7">Terima tersedia di tahap wawancara atau kunjungan rumah.</p>
                            @endif
                            <form method="POST" action="{{ route('applicant.reject', $a) }}">
                                @csrf
                                <x-dashboard.field name="status" id="reject-status" label="Hasil" type="select" :options="['ditolak' => 'Ditolak', 'mengundurkan_diri' => 'Mengundurkan diri']" required />
                                <x-dashboard.field name="note" id="reject-note" label="Alasan" type="textarea" required />
                                <button class="btn btn-sm btn-light-danger w-100">Simpan keputusan</button>
                            </form>
                        </div>
                    </div>
                @endcan
            @endunless
            <div class="card">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Riwayat</div></div>
                <div class="card-body">
                    @foreach (array_reverse($a->history ?? []) as $h)
                        <div class="mb-4">
                            <div class="fw-bold">{{ App\Models\Applicant::STATUSES[$h['status']] ?? $h['status'] }}</div>
                            <div class="text-muted fs-8">{{ \Illuminate\Support\Carbon::parse($h['at'])->translatedFormat('j M Y H:i') }} · {{ $h['by'] ?? 'Formulir' }}</div>
                            @if ($h['note'])<div class="fs-7">{{ $h['note'] }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-dashboard.page>
