<x-dashboard.page title="Laporan Konten" :crumbs="['Postingan', 'Forum', 'Laporan']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Laporan Belum Ditangani <span class="badge badge-light-danger ms-3">{{ $open }}</span></div>
            <div class="card-toolbar">
                <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="all" value="1" data-dt-filter="datatable"
                    onchange="this.value = this.checked ? 1 : 0"><span class="form-check-label">Tampilkan yang sudah ditangani</span></label>
            </div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('report.datatable')" :order="[[1, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'created_at', 'name' => 'reports.created_at', 'title' => 'Waktu', 'searchable' => false],
                ['data' => 'user.name', 'title' => 'Pelapor'],
                ['data' => 'reason', 'title' => 'Alasan'],
                ['data' => 'target', 'title' => 'Konten', 'orderable' => false, 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
