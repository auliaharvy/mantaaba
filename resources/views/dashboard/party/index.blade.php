<x-dashboard.page title="Pihak" :crumbs="['Keuangan', 'COA & Master', 'Pihak']">
    @include('dashboard.coa.tabs', ['active' => 'party'])
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-4 fw-bolder">Pihak</div>
            <div class="card-toolbar gap-2">
                <select class="form-select form-select-sm w-auto" name="type" data-dt-filter="datatable" aria-label="Filter jenis"><option value="">Semua jenis</option>@foreach (App\Models\Party::TYPES as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                @can('coa-create')<a href="{{ route('party.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah</a>@endcan
            </div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('party.datatable')" :order="[[2, 'asc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'code', 'title' => 'Kode'],
                ['data' => 'name', 'title' => 'Nama'],
                ['data' => 'type', 'title' => 'Jenis', 'searchable' => false],
                ['data' => 'phone', 'title' => 'Telepon'],
                ['data' => 'is_active', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
