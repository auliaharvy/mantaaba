<x-dashboard.page title="Galeri" :crumbs="['Website', 'Galeri']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Album Galeri</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('gallery.create') }}"><i class="fa fa-plus"></i> Tambah Album</a></div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('gallery.datatable')" :order="[[3, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'title' => 'Judul'],
                ['data' => 'photos_count', 'title' => 'Foto', 'searchable' => false],
                ['data' => 'published_at', 'title' => 'Terbit', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
