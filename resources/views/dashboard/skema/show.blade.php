{{-- Susunan modul & materi (PRD 9.6). Urutan lewat drag lalu "Simpan urutan". --}}
@php
    $canUpdate = auth()->user()->can('update', $skema);
    $unitOptions = $skema->units->mapWithKeys(fn ($u) => [$u->id => $u->code . ' · ' . $u->title])->all();
@endphp
<x-dashboard.page :title="$skema->code . ' · ' . $skema->name" :crumbs="['Pembelajaran', 'Skema', $skema->code]">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-6 align-items-center">
            <div><div class="text-muted fs-7">Waktu</div><div class="fw-bold">{{ $skema->month_label ?: '-' }}</div></div>
            <div><div class="text-muted fs-7">Nilai lulus</div><div class="fw-bold">≥ {{ $skema->passScore() }}</div></div>
            <div><div class="text-muted fs-7">Kurikulum</div><div class="fw-bold">{{ $skema->units->count() ? $skema->units->count() . ' unit · ' : '' }}{{ $skema->modules->count() }} modul · {{ $skema->criteriaCount() }} KUK</div></div>
            <div class="flex-grow-1" style="min-width: 0"><div class="text-muted fs-7">{{ $skema->title ? 'Skema' : 'Deskripsi' }}</div><div>{{ $skema->title ?: ($skema->description ?: '-') }}</div></div>
            <div class="d-flex flex-wrap gap-2">
                @if ($skema->units->isNotEmpty())<a href="{{ route('skema.pdf', $skema) }}" class="btn btn-sm btn-light-primary"><i class="fa fa-file-pdf"></i> Pemetaan (PDF)</a>@endif
                <a href="{{ route('skema.export', $skema) }}" class="btn btn-sm btn-light"><i class="fa fa-download"></i> Unduh JSON</a>
                @if ($canUpdate)<button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#import-json"><i class="fa fa-upload"></i> Impor JSON</button>
                    <a href="{{ route('skema.edit', $skema) }}" class="btn btn-sm btn-light-success"><i class="fa fa-pen"></i> Ubah skema</a>@endif
                <a href="{{ route('skema.index') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i></a>
            </div>
        </div>
    </div>

    @if ($canUpdate)
        <form method="POST" action="{{ route('skema.import', $skema) }}" enctype="multipart/form-data" class="collapse card mb-5" id="import-json">
            @csrf
            <div class="card-body d-flex flex-wrap gap-3 align-items-end">
                <div class="flex-grow-1"><x-dashboard.field name="file" label="Template JSON skema {{ $skema->code }}" type="file" accept=".json,application/json" required
                    help="Unit dicocokkan berdasarkan kode: unit lama diperbarui (materi tetap), unit baru ditambahkan, unit tanpa materi yang tidak ada di JSON dihapus. Contoh format: Unduh JSON." /></div>
                <button class="btn btn-sm btn-primary mb-6">Impor</button>
            </div>
        </form>
    @endif

    @if ($skema->units->isNotEmpty())
        <div class="card mb-5">
            <div class="card-header"><div class="card-title fs-5 fw-bolder">Pemetaan standar kompetensi</div>
                <div class="card-toolbar"><button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#function-map">Tampilkan / sembunyikan</button></div></div>
            <div class="collapse show" id="function-map">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-bordered align-middle mb-0 fs-7">
                        <thead><tr class="fw-bold bg-light text-center"><th>Tujuan utama</th><th>Fungsi kunci</th><th>Fungsi utama</th><th>Fungsi dasar / unit kompetensi</th></tr></thead>
                        <tbody>
                            @foreach ($skema->functionGroups() as $g => [$keyFunction, $groupUnits])
                                @foreach ($groupUnits as $i => $unit)
                                    <tr>
                                        @if ($g === 0 && $i === 0)<td rowspan="{{ $skema->units->count() }}" class="fw-bold bg-light-primary w-25">{{ strip_tags($skema->objectives) ?: '—' }}</td>@endif
                                        @if ($i === 0)<td rowspan="{{ $groupUnits->count() }}" class="w-25">{{ $keyFunction ?: '—' }}</td>@endif
                                        <td>{{ $unit->main_function ?: '—' }}</td>
                                        <td><span class="text-muted text-nowrap">{{ $unit->code }}</span> · {{ $unit->title }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('skema.reorder', $skema) }}" id="order-form">
        @csrf
        {{-- unit kompetensi → modul (satu per elemen, berisi KUK) → materi; modul tanpa unit di kelompok terakhir --}}
        @php
            $groups = $skema->units->map(fn ($u) => [$u, $skema->modules->where('competency_unit_id', $u->id)]);
            $loose = $skema->modules->whereNull('competency_unit_id');
            if ($loose->isNotEmpty()) {
                $groups->push([null, $loose]);
            }
        @endphp
        @forelse ($groups as [$unit, $unitModules])
            <div class="{{ $unit ? 'border border-primary border-opacity-25 rounded p-4 mb-6' : 'mb-6' }}">
                @if ($unit)
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <span class="badge badge-primary">Unit kompetensi</span>
                        <span class="fw-bolder fs-4 flex-grow-1" style="min-width: 0">{{ $unit->code }} · {{ $unit->title }}</span>
                        <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="collapse" data-bs-target="#unit-{{ $unit->id }}"><i class="fa fa-info-circle"></i> Detail unit</button>
                        @if ($canUpdate)<button type="button" class="btn btn-sm btn-icon btn-light" data-bs-toggle="collapse" data-bs-target="#edit-unit-{{ $unit->id }}" title="Ubah unit"><i class="fa fa-pen"></i></button>@endif
                    </div>
                    @if ($unit->main_function || $unit->key_function)<div class="text-muted fs-7 mb-3">Fungsi utama: <b>{{ $unit->main_function ?: '—' }}</b> · Fungsi kunci: {{ $unit->key_function ?: '—' }}</div>@endif
                    @include('dashboard.skema.unit-detail', ['unit' => $unit, 'collapseId' => 'unit-' . $unit->id])
                @elseif ($skema->units->isNotEmpty())
                    <div class="fw-bolder fs-5 mb-3">Modul lain</div>
                @endif
                <div class="module-sortable">
                    @foreach ($unitModules as $module)
                        <div class="card mb-4 module-item" data-id="{{ $module->id }}">
                            <div class="card-header align-items-center flex-nowrap gap-3 min-h-55px">
                                @if ($canUpdate)<span class="module-handle cursor-move text-gray-500 fs-4" title="Seret untuk mengurutkan modul"><i class="fa fa-grip-vertical"></i></span>@endif
                                <div class="flex-grow-1" style="min-width: 0">
                                    <div class="fw-bolder fs-6">{{ $module->code ? $module->code . ' · ' : '' }}{{ $module->title }}</div>
                                    @if ($module->summary)<div class="text-muted fs-7 text-truncate">{{ $module->summary }}</div>@endif
                                </div>
                                @if ($canUpdate)
                                    <a href="{{ route('lesson.create', $module) }}" class="btn btn-sm btn-light-primary"><i class="fa fa-plus"></i> Materi</a>
                                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-toggle="collapse" data-bs-target="#edit-module-{{ $module->id }}" title="Ubah modul"><i class="fa fa-pen"></i></button>
                                    @if ($module->lessons->isEmpty())<button type="button" class="btn btn-sm btn-icon btn-light-danger" data-delete-url="{{ route('module.destroy', $module) }}" title="Hapus modul"><i class="fa fa-trash"></i></button>@endif
                                @endif
                            </div>
                            <div class="card-body py-3">
                                @include('dashboard.skema.criteria', ['module' => $module])
                                <ul class="list-unstyled lesson-sortable mb-0">
                                    @forelse ($module->lessons as $lesson)
                                        <li class="lesson-item d-flex align-items-center gap-3 border rounded px-3 py-2 mb-2 bg-white" data-id="{{ $lesson->id }}">
                                            @if ($canUpdate)<span class="lesson-handle cursor-move text-gray-400" title="Seret untuk mengurutkan"><i class="fa fa-grip-lines"></i></span>@endif
                                            <span class="badge badge-light-{{ $lesson->isAssignment() ? 'warning' : 'primary' }}">{{ App\Models\Lesson::TYPES[$lesson->type] }}</span>
                                            <span class="flex-grow-1 {{ $lesson->is_published ? '' : 'text-muted' }}">{{ $lesson->title }}@unless ($lesson->is_published) <span class="fs-8">(draf)</span>@endunless</span>
                                            @if ($lesson->due_days)<span class="text-muted fs-8">tenggat {{ $lesson->due_days }} hari</span>@endif
                                            @if ($canUpdate)<a href="{{ route('lesson.edit', $lesson) }}" class="btn btn-sm btn-icon btn-light-success" title="Ubah"><i class="fa fa-pen"></i></a>@endif
                                            @can('delete', $skema)<button type="button" class="btn btn-sm btn-icon btn-light-danger" data-delete-url="{{ route('lesson.destroy', $lesson) }}" title="Hapus"><i class="fa fa-trash"></i></button>@endcan
                                        </li>
                                    @empty
                                        <li class="text-muted fs-7">Belum ada materi.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="card mb-5"><div class="card-body text-muted">Belum ada unit kompetensi atau modul.</div></div>
        @endforelse
        @if ($canUpdate && $skema->modules->isNotEmpty())<div class="text-end mb-5"><button class="btn btn-sm btn-primary">Simpan urutan</button></div>@endif
    </form>

    @if ($skema->standard && array_filter($skema->standard))
        <div class="card mb-5">
            <div class="card-header"><div class="card-title fs-5 fw-bolder">Standar kompetensi</div>
                <div class="card-toolbar"><button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#standard-detail">Tampilkan</button></div></div>
            <div class="collapse" id="standard-detail">
                <div class="card-body fs-7">
                    @if ($skema->objectives)<p><b>Tujuan utama:</b> {{ $skema->objectives }}</p>@endif
                    @if (! empty($skema->standard['competency_map']))
                        <div class="table-responsive mb-5"><table class="table table-sm table-row-bordered">
                            <thead><tr class="fw-bold"><th>Fungsi kunci</th><th>Fungsi utama</th><th>Unit</th></tr></thead>
                            @foreach ($skema->standard['competency_map'] as $row)
                                <tr><td>{{ $row['key_function'] ?? '' }}</td><td>{{ $row['main_function'] ?? '' }}@if (! empty($row['basic_functions']))<div class="text-muted">{{ implode(' · ', $row['basic_functions']) }}</div>@endif</td><td class="text-nowrap">{{ implode(', ', $row['units'] ?? []) }}</td></tr>
                            @endforeach
                        </table></div>
                    @endif
                    @if (! empty($skema->standard['final_portfolio']))
                        <div class="fw-bolder mb-2">Paket portofolio akhir</div>
                        <ul class="mb-5">@foreach ($skema->standard['final_portfolio'] as $p)<li>{{ $p['output'] }} <span class="text-muted">({{ $p['units'] }})</span></li>@endforeach</ul>
                    @endif
                    @foreach (array_merge($skema->standard['notes'] ?? [], $skema->standard['assessment_guide'] ?? []) as $g)
                        <div class="mb-2"><b>{{ $g['topic'] }}:</b> {{ $g['text'] }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($canUpdate)
        @foreach ($skema->units as $unit)
            <form method="POST" action="{{ route('skema.unit-update', $unit) }}" class="collapse card mb-5" id="edit-unit-{{ $unit->id }}">
                @csrf @method('PATCH')
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Ubah unit kompetensi {{ $unit->code }}</div></div>
                <div class="card-body row">
                    <div class="col-12"><x-dashboard.field name="title" :id="'unit-title-' . $unit->id" label="Judul unit (fungsi dasar)" :value="$unit->title" required /></div>
                    <div class="col-md-6"><x-dashboard.field name="key_function" :id="'unit-key-' . $unit->id" label="Fungsi kunci" type="textarea" :value="$unit->key_function" /></div>
                    <div class="col-md-6"><x-dashboard.field name="main_function" :id="'unit-main-' . $unit->id" label="Fungsi utama" :value="$unit->main_function" /></div>
                    <div class="col-12"><x-dashboard.field name="description" :id="'unit-desc-' . $unit->id" label="Deskripsi unit" type="textarea" :value="$unit->description" /></div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Simpan unit</button></div>
            </form>
        @endforeach
        @foreach ($skema->modules as $module)
            <form method="POST" action="{{ route('module.update', $module) }}" class="collapse card mb-5" id="edit-module-{{ $module->id }}">
                @csrf @method('PATCH')
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Ubah modul: {{ $module->title }}</div></div>
                <div class="card-body row">
                    @if ($unitOptions)<div class="col-12"><x-dashboard.field name="competency_unit_id" :id="'module-unit-' . $module->id" label="Unit kompetensi" type="select" :value="$module->competency_unit_id" :options="$unitOptions" placeholder="— Tanpa unit —" /></div>@endif
                    <div class="col-md-2"><x-dashboard.field name="code" :id="'module-code-' . $module->id" label="Kode" :value="$module->code" /></div>
                    <div class="col-md-10"><x-dashboard.field name="title" :id="'module-title-' . $module->id" label="Judul" :value="$module->title" required /></div>
                    <div class="col-12"><x-dashboard.field name="summary" :id="'module-summary-' . $module->id" label="Ringkasan" type="textarea" :value="$module->summary" /></div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Simpan modul</button></div>
            </form>
        @endforeach
        <form method="POST" action="{{ route('module.store', $skema) }}" class="card">
            @csrf
            <div class="card-header"><div class="card-title fs-5 fw-bolder">Tambah modul</div></div>
            <div class="card-body row">
                @if ($unitOptions)<div class="col-12"><x-dashboard.field name="competency_unit_id" label="Unit kompetensi" type="select" :options="$unitOptions" placeholder="— Tanpa unit —" /></div>@endif
                <div class="col-md-2"><x-dashboard.field name="code" label="Kode" placeholder="SIA-1" /></div>
                <div class="col-md-10"><x-dashboard.field name="title" label="Judul" required /></div>
                <div class="col-12"><x-dashboard.field name="summary" label="Ringkasan" type="textarea" /></div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Tambah</button></div>
        </form>
    @endif

    @if ($canUpdate)
        <x-slot name="javascript">
            <script src="{{ asset('plugins/custom/draggable/draggable.bundle.js') }}"></script>
            <script>
                const keep = (s) => s.on('sortable:sort', (e) => { if (e.dragEvent.overContainer !== e.dragEvent.sourceContainer) e.cancel(); });
                keep(new Sortable.default(document.querySelectorAll('.module-sortable'), { draggable: '.module-item', handle: '.module-handle', mirror: { constrainDimensions: true } }));
                keep(new Sortable.default(document.querySelectorAll('.lesson-sortable'), { draggable: '.lesson-item', handle: '.lesson-handle', mirror: { constrainDimensions: true } }));
                document.getElementById('order-form').addEventListener('submit', function () {
                    this.querySelectorAll('input[data-order]').forEach((el) => el.remove());
                    [['.module-item', 'modules[]'], ['.lesson-item', 'lessons[]']].forEach(([selector, name]) => this.querySelectorAll(selector + '[data-id]').forEach((el) => {
                        if (el.classList.contains('draggable-mirror')) return;
                        const input = Object.assign(document.createElement('input'), { type: 'hidden', name, value: el.dataset.id });
                        input.dataset.order = 1; this.appendChild(input);
                    }));
                });
            </script>
        </x-slot>
    @endif
</x-dashboard.page>
