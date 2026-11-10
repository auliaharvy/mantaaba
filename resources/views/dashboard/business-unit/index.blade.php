<x-dashboard.page title="Unit Bisnis" :crumbs="['Website', 'Unit Bisnis']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Unit Bisnis & Laboratorium</div>
            @can('cms-create')
                <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('business-unit.create') }}"><i class="fa fa-plus"></i> Tambah Unit</a></div>
            @endcan
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('business-unit.datatable')" :order="[[4, 'asc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'name', 'title' => 'Nama'],
                ['data' => 'type', 'title' => 'Jenis', 'searchable' => false],
                ['data' => 'field', 'title' => 'Bidang'],
                ['data' => 'sort_order', 'title' => 'Urutan', 'searchable' => false],
                ['data' => 'is_public', 'title' => 'Situs', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
