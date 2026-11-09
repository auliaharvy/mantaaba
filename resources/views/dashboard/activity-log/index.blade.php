<x-dashboard.page title="Log Aktivitas" :crumbs="['Pengaturan', 'Log Aktivitas']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Log Aktivitas</div>
        </div>
        <div class="card-body p-lg-8">
            <div class="row g-3 mb-5">
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="subject_type" data-dt-filter="datatable" aria-label="Filter subjek">
                        <option value="">Semua subjek</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject }}">{{ class_basename($subject) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control form-control-sm" name="date" data-dt-filter="datatable" aria-label="Filter tanggal">
                </div>
            </div>
            <x-dashboard.datatable :url="route('activity-log.datatable')" :order="[[1, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'created_at', 'title' => 'Waktu'],
                ['data' => 'user.name', 'title' => 'Pengguna'],
                ['data' => 'action', 'name' => 'activity_logs.action', 'title' => 'Aksi', 'class' => ''],
                ['data' => 'subject', 'title' => 'Subjek', 'orderable' => false, 'searchable' => false],
                ['data' => 'detail', 'title' => 'Perubahan', 'orderable' => false, 'searchable' => false],
            ]" />
        </div>
    </div>
</x-dashboard.page>
