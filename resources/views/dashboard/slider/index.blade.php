<x-dashboard.page title="Slider Beranda" :crumbs="['Website', 'Slider']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Slider Beranda</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('slider.create') }}"><i class="fa fa-plus"></i> Tambah Slider</a></div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('slider.datatable')" :order="[[4, 'asc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'preview', 'title' => 'Gambar', 'orderable' => false, 'searchable' => false],
                ['data' => 'title', 'title' => 'Judul'],
                ['data' => 'subtitle', 'title' => 'Subjudul'],
                ['data' => 'sort_order', 'title' => 'Urutan', 'searchable' => false],
                ['data' => 'is_active', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
