<x-dashboard.page title="Acara" :crumbs="['Kegiatan', 'Acara']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Daftar Acara</div>
            @can('create', App\Models\Event::class)
                <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('event.create') }}"><i class="fa fa-plus"></i> Tambah Acara</a></div>
            @endcan
        </div>
        <div class="card-body p-lg-8">
            <div class="row mb-5">
                <div class="col-md-4">
                    <select class="form-select form-select-sm" name="status" data-dt-filter="datatable" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach (App\Models\Event::STATUSES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
            <x-dashboard.datatable :url="route('event.datatable')" :order="[[3, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'title', 'name' => 'events.title', 'title' => 'Judul'],
                ['data' => 'type', 'name' => 'events.type', 'title' => 'Jenis', 'searchable' => false],
                ['data' => 'starts_at', 'name' => 'events.starts_at', 'title' => 'Mulai', 'searchable' => false],
                ['data' => 'seats', 'title' => 'Pendaftar', 'orderable' => false, 'searchable' => false],
                ['data' => 'status', 'name' => 'events.status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
