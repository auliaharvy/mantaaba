<x-dashboard.page title="Pengumuman" :crumbs="['Anggota', 'Pengumuman']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Pengumuman</div>
            @can('announcement-create')<div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('announcement.create') }}"><i class="fa fa-plus"></i> Tambah</a></div>@endcan
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('announcement.datatable')" :order="[[3, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'title' => 'Judul'],
                ['data' => 'audience', 'title' => 'Sasaran', 'searchable' => false],
                ['data' => 'published_at', 'title' => 'Terbit', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
