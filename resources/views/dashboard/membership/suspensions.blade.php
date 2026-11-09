<x-dashboard.page title="Usulan Skors" :crumbs="['Organisasi', 'Data Anggota', 'Usulan Skors']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Anggota Aktif yang Menunggak Iuran</div>
        </div>
        <div class="card-body p-lg-8">
            <div class="alert alert-light-warning">
                Daftar ini berisi anggota aktif yang belum membayar iuran {{ now()->year - 1 }} maupun {{ now()->year }}, dihitung mulai 1 Mei.
                Status <strong>tidak berubah otomatis</strong>: tinjau lalu klik <em>Skors</em> per anggota.
            </div>
            <x-dashboard.datatable :url="route('membership.suspensions.datatable')" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'member_number', 'title' => 'No. Anggota'],
                ['data' => 'user.name', 'title' => 'Nama'],
                ['data' => 'organization.name', 'title' => 'PC'],
                ['data' => 'last_paid', 'title' => 'Iuran terakhir', 'orderable' => false, 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
