<x-dashboard.page title="Halaman" :crumbs="['Website', 'Halaman']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Halaman Situs</div>
            @can('cms-create')
                <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('page.create') }}"><i class="fa fa-plus"></i> Tambah Halaman</a></div>
            @endcan
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('page.datatable')" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'title' => 'Judul'],
                ['data' => 'slug', 'title' => 'Alamat'],
                ['data' => 'is_published', 'title' => 'Status', 'searchable' => false],
                ['data' => 'updated_at', 'title' => 'Diubah', 'searchable' => false],
                ['data' => 'editor.name', 'title' => 'Oleh'],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
