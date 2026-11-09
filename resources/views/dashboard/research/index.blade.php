<x-dashboard.page title="Jurnal" :crumbs="['Postingan', 'Jurnal']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Daftar Jurnal</div>
            @can('create', App\Models\Research::class)
                <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('research.create') }}"><i class="fa fa-plus"></i> Tambah Jurnal</a></div>
            @endcan
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('research.datatable')" :order="[[4, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'title' => 'Judul'],
                ['data' => 'authors', 'title' => 'Penulis'],
                ['data' => 'category', 'title' => 'Kategori'],
                ['data' => 'date_of_publish', 'title' => 'Terbit', 'searchable' => false],
                ['data' => 'downloads', 'title' => 'Unduhan', 'searchable' => false],
                ['data' => 'status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
