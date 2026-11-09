<x-dashboard.page title="Dokumen" :crumbs="['Website', 'Dokumen']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Peraturan Organisasi & Dokumen</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('document.create') }}"><i class="fa fa-plus"></i> Tambah Dokumen</a></div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('document.datatable')" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'title' => 'Judul'],
                ['data' => 'category', 'title' => 'Kategori'],
                ['data' => 'published_at', 'title' => 'Terbit', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
