<x-dashboard.page title="Data Anggota" :crumbs="['Anggota', 'Data Anggota']">
    @if (session('import_errors'))
        <div class="alert alert-warning"><ul class="mb-0 fs-7">@foreach (session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="card">
        <div class="card-header flex-wrap gap-3 py-4">
            <div class="card-title fs-3 fw-bolder">Anggota</div>
            <div class="card-toolbar gap-2">
                @can('member-export')<a href="{{ route('member.export') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-file-csv"></i> Export CSV</a>@endcan
                @if (auth()->user()->can('member-create') && auth()->user()->can('member-private'))
                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#import-box"><i class="fa fa-upload"></i> Import CSV</button>
                @endif
                @can('member-create')<a href="{{ route('member.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah</a>@endcan
            </div>
        </div>
        <div class="card-body p-lg-8">
            <form method="POST" action="{{ route('member.import') }}" enctype="multipart/form-data" class="collapse mb-6 border rounded p-5 bg-light" id="import-box">
                @csrf
                <p class="fs-7 mb-3">Kolom (baris pertama): <code>{{ implode(',', array_diff(App\Http\Controllers\MemberController::CSV_COLUMNS, ['member_number'])) }}</code>. Wajib: name, category. Nomor anggota dibuat otomatis; akun login dibuat bila email diisi.</p>
                <div class="d-flex gap-3"><input type="file" name="file" accept=".csv" class="form-control form-control-sm" required aria-label="Berkas CSV"><button class="btn btn-sm btn-primary">Import</button></div>
            </form>
            <div class="d-flex flex-wrap gap-3 mb-4">
                @foreach (['category' => ['Semua kategori', App\Models\Member::CATEGORIES], 'status' => ['Semua status', App\Models\Member::STATUSES], 'phase' => ['Semua fase', App\Models\Member::PHASES], 'batch_id' => ['Semua angkatan', $batches], 'unit' => ['Semua penempatan', $units]] as $name => [$placeholder, $options])
                    <select name="{{ $name }}" class="form-select form-select-sm w-auto" data-dt-filter="datatable" aria-label="{{ $placeholder }}">
                        <option value="">{{ $placeholder }}</option>@foreach ($options as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                @endforeach
            </div>
            <x-dashboard.datatable :url="route('member.datatable')" :order="[[1, 'asc']]" :columns="array_values(array_filter([
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'member_number', 'title' => 'Nomor'],
                ['data' => 'name', 'title' => 'Nama'],
                ['data' => 'category', 'title' => 'Kategori', 'searchable' => false],
                ['data' => 'status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'phase', 'title' => 'Fase', 'searchable' => false],
                ['data' => 'batch_code', 'name' => 'batch.code', 'title' => 'Angkatan', 'orderable' => false, 'searchable' => false],
                ['data' => 'placement', 'title' => 'Penempatan', 'orderable' => false, 'searchable' => false],
                auth()->user()->can('member-private') ? ['data' => 'district', 'title' => 'Kecamatan'] : null,
                ['data' => 'action', 'title' => ''],
            ]))" />
        </div>
    </div>
</x-dashboard.page>
