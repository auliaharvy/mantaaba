<x-dashboard.page title="Pengurus Cabang" :crumbs="['Organisasi', 'Pengurus Cabang']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Daftar Pengurus Cabang (PC)</div>
            @can('create', App\Models\Organization::class)
                <div class="card-toolbar">
                    <a class="btn btn-sm btn-light-primary" href="{{ route('organization.create') }}"><i class="fa fa-plus"></i> Tambah PC</a>
                </div>
            @endcan
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('organization.datatable')" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'name', 'title' => 'Nama'],
                ['data' => 'code', 'title' => 'Kode'],
                ['data' => 'city', 'title' => 'Kota/Kab.'],
                ['data' => 'members_count', 'title' => 'Anggota Aktif', 'searchable' => false],
                ['data' => 'is_active', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
