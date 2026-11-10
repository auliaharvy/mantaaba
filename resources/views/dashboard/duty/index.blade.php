{{-- Jadwal piket (PRD 9.9) per tanggal, generator rotasi, ganti/tukar, verifikasi. --}}
@php
    $colors = ['terjadwal' => 'light-primary', 'selesai' => 'light-success', 'diverifikasi' => 'success', 'terlewat' => 'light-danger'];
    $canUpdate = auth()->user()->can('dorm-update');
    $canVerify = auth()->user()->can('duty-verify');
@endphp
<x-dashboard.page title="Jadwal Piket" :crumbs="['Asrama', 'Jadwal Piket']">
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-3 align-items-end">
            <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">
                <div><label class="form-label fs-7" for="dari">Dari</label><input id="dari" type="date" name="dari" value="{{ $from->toDateString() }}" class="form-control form-control-sm"></div>
                <div><label class="form-label fs-7" for="sampai">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ $to->toDateString() }}" class="form-control form-control-sm"></div>
                <div><label class="form-label fs-7" for="area">Area</label><select id="area" name="area" class="form-select form-select-sm"><option value="">Semua area</option>@foreach ($areas as $a)<option value="{{ $a->id }}" @selected(request('area') == $a->id)>{{ $a->name }}</option>@endforeach</select></div>
                <button class="btn btn-sm btn-light-primary">Tampilkan</button>
            </form>
            <div class="ms-auto d-flex flex-wrap gap-2">
                <a href="{{ route('duty.report') }}" class="btn btn-sm btn-light"><i class="fa fa-chart-bar"></i> Laporan</a>
                <a href="{{ route('duty-area.index') }}" class="btn btn-sm btn-light"><i class="fa fa-list-check"></i> Area & ceklis</a>
                @can('dorm-create')<button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#generator"><i class="fa fa-magic"></i> Buat jadwal rotasi</button>@endcan
            </div>
        </div>
    </div>

    @can('dorm-create')
        <form method="POST" action="{{ route('duty.generate') }}" class="card mb-5 collapse {{ $errors->has('member_ids') || $errors->has('to') ? 'show' : '' }}" id="generator">
            @csrf
            <div class="card-header"><div class="card-title fs-5 fw-bolder">Generator rotasi (round-robin, tidak dua kali sehari)</div></div>
            <div class="card-body row">
                <div class="col-md-3"><x-dashboard.field name="duty_area_id" id="gen-area" label="Area" type="select" :options="$areas->where('is_active', true)->pluck('name', 'id')" required /></div>
                <div class="col-md-3"><x-dashboard.field name="from" label="Dari" type="date" :value="today()" required /></div>
                <div class="col-md-3"><x-dashboard.field name="to" label="Sampai" type="date" :value="today()->addDays(6)" required /></div>
                <div class="col-md-3"><x-dashboard.field name="per_shift" label="Orang per shift" type="number" min="1" max="10" value="1" required /></div>
                <div class="col-12 mb-4"><label class="form-label required">Shift</label><div class="d-flex gap-5">
                    @foreach (App\Models\Duty::SHIFTS as $v => $l)<label class="form-check"><input class="form-check-input" type="checkbox" name="shifts[]" value="{{ $v }}" @checked($v === 'pagi')> <span class="form-check-label">{{ $l }}</span></label>@endforeach</div></div>
                <div class="col-12">
                    <label class="form-label required">Anggota yang dirotasi</label>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('[name=&quot;member_ids[]&quot;]').forEach(c => c.checked = true)">Pilih semua</button>
                        @foreach ($batches as $b)<button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('[name=&quot;member_ids[]&quot;]').forEach(c => c.checked = c.dataset.batch === '{{ $b->id }}')">Angkatan {{ $b->code }}</button>@endforeach
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($members as $m)
                            <input type="checkbox" class="btn-check" name="member_ids[]" value="{{ $m->id }}" id="gm{{ $m->id }}" data-batch="{{ $m->batch_id }}" autocomplete="off">
                            <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-light-primary" for="gm{{ $m->id }}">{{ $m->name }}</label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Buat jadwal</button></div>
        </form>
    @endcan

    @forelse ($duties as $date => $rows)
        <div class="card mb-4">
            <div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, j F Y') }}</div></div>
            <div class="card-body py-2">
                @foreach ($rows as $duty)
                    <div class="d-flex flex-wrap align-items-center gap-3 py-2 border-bottom">
                        @if ($canUpdate && $duty->status === 'terjadwal')<input type="checkbox" class="form-check-input swap-pick" value="{{ $duty->id }}" aria-label="Pilih untuk ditukar">@endif
                        <span class="badge badge-light w-60px">{{ App\Models\Duty::SHIFTS[$duty->shift] }}</span>
                        <span class="fw-bold w-150px">{{ $duty->area->name }}</span>
                        <span class="flex-grow-1">{{ $duty->member->name }}</span>
                        <span class="badge badge-{{ $colors[$duty->status] }}">{{ App\Models\Duty::STATUSES[$duty->status] }}</span>
                        @if ($duty->photo)<a href="{{ route('duty.photo', $duty) }}" target="_blank" class="btn btn-sm btn-icon btn-light" title="Foto"><i class="fa fa-image"></i></a>@endif
                        @if ($canVerify && $duty->status === 'selesai')
                            <form method="POST" action="{{ route('duty.verify', $duty) }}">@csrf<button class="btn btn-sm btn-light-success">Verifikasi</button></form>
                        @endif
                        @if ($canUpdate && $duty->status === 'terjadwal')
                            <form method="POST" action="{{ route('duty.update', $duty) }}" class="d-flex gap-1">@csrf @method('PATCH')
                                <select name="member_id" class="form-select form-select-sm w-150px" onchange="this.form.submit()" aria-label="Ganti petugas">
                                    <option value="">Ganti petugas…</option>@foreach ($members as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                                </select></form>
                        @endif
                        @can('dorm-delete')<button type="button" class="btn btn-sm btn-icon btn-light-danger" data-delete-url="{{ route('duty.destroy', $duty) }}" title="Hapus"><i class="fa fa-trash"></i></button>@endcan
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-muted">Belum ada jadwal piket pada rentang ini.</div></div>
    @endforelse

    @if ($canUpdate)
        <form method="POST" action="{{ route('duty.swap') }}" id="swap-form" class="position-fixed bottom-0 end-0 m-5 d-none" style="z-index: 100">
            @csrf <input type="hidden" name="first"><input type="hidden" name="second">
            <button class="btn btn-primary shadow"><i class="fa fa-exchange-alt"></i> Tukar 2 jadwal terpilih</button>
        </form>
        <x-slot name="javascript">
            <script>
                const picks = document.querySelectorAll('.swap-pick'), form = document.getElementById('swap-form');
                picks.forEach((p) => p.addEventListener('change', () => {
                    const chosen = [...picks].filter((x) => x.checked);
                    if (chosen.length > 2) { p.checked = false; return; }
                    form.classList.toggle('d-none', chosen.length !== 2);
                    if (chosen.length === 2) { form.first.value = chosen[0].value; form.second.value = chosen[1].value; }
                }));
            </script>
        </x-slot>
    @endif
</x-dashboard.page>
