<x-dashboard.page title="Kesehatan" :crumbs="['Anggota', 'Kesehatan']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Catatan Kesehatan</div>
            @can('health-create')<div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('health.create') }}"><i class="fa fa-plus"></i> Catat</a></div>@endcan
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('health.datatable')" :order="[[1, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'date', 'title' => 'Tanggal', 'searchable' => false],
                ['data' => 'member.name', 'name' => 'member.name', 'title' => 'Anggota', 'orderable' => false],
                ['data' => 'complaint', 'title' => 'Keluhan'],
                ['data' => 'action', 'name' => 'action', 'title' => 'Tindakan', 'searchable' => false],
                ['data' => 'cost', 'title' => 'Biaya', 'searchable' => false],
                ['data' => 'actions', 'title' => '', 'orderable' => false, 'searchable' => false, 'class' => 'text-end'],
            ]" />
        </div>
    </div>
</x-dashboard.page>
