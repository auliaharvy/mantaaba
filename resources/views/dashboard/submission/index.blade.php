<x-dashboard.page title="Tugas Masuk" :crumbs="['Pembelajaran', 'Tugas Masuk']">
    <div class="card">
        <div class="card-header flex-wrap gap-3 py-4">
            <div class="card-title fs-3 fw-bolder">Tugas Masuk</div>
            <div class="card-toolbar gap-2">
                <select name="state" class="form-select form-select-sm w-auto" data-dt-filter="datatable" aria-label="Status"><option value="belum">Belum dinilai</option><option value="sudah">Sudah dinilai</option><option value="semua">Semua</option></select>
                <select name="skema" class="form-select form-select-sm w-auto" data-dt-filter="datatable" aria-label="Skema"><option value="">Semua skema</option>@foreach ($skemas as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                <select name="batch" class="form-select form-select-sm w-auto" data-dt-filter="datatable" aria-label="Angkatan"><option value="">Semua angkatan</option>@foreach ($batches as $id => $code)<option value="{{ $id }}">{{ $code }}</option>@endforeach</select>
            </div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('submission.datatable')" :order="[[5, 'asc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'user.name', 'name' => 'user.name', 'title' => 'Siswa', 'orderable' => false],
                ['data' => 'skema', 'title' => 'Skema', 'orderable' => false, 'searchable' => false],
                ['data' => 'lesson.title', 'name' => 'lesson.title', 'title' => 'Tugas', 'orderable' => false],
                ['data' => 'score', 'title' => 'Nilai', 'searchable' => false],
                ['data' => 'submitted_at', 'title' => 'Dikirim', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
