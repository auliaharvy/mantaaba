<x-dashboard.page title="Angkatan" :crumbs="['Penerimaan', 'Angkatan']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Angkatan</div>
            @can('batch-create')<div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('batch.create') }}"><i class="fa fa-plus"></i> Tambah Angkatan</a></div>@endcan
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('batch.datatable')" :order="[[1, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'code', 'title' => 'Kode'],
                ['data' => 'name', 'title' => 'Nama'],
                ['data' => 'window', 'title' => 'Pendaftaran', 'orderable' => false, 'searchable' => false],
                ['data' => 'accepted', 'title' => 'Diterima / kuota', 'orderable' => false, 'searchable' => false],
                ['data' => 'status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
