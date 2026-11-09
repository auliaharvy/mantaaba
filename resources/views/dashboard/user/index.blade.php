<x-dashboard.page title="Pengguna & Hak Akses" :crumbs="['Sistem', 'Pengguna']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Pengguna</div>
            <div class="card-toolbar">
                @can('create', App\Models\User::class)
                    <a class="btn btn-sm btn-light-primary" href="{{ route('user.create') }}"><i class="fa fa-plus"></i> Tambah Pengguna</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-lg-8">
            <div class="row g-3 mb-5">
                <div class="col-sm-4 col-lg-3">
                    <select class="form-select form-select-sm" data-dt-filter="role" aria-label="Filter role">
                        <option value="">Semua role</option>
                        @foreach (config('mantaaba.roles') as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-sm-4 col-lg-3">
                    <select class="form-select form-select-sm" data-dt-filter="status" aria-label="Filter status">
                        <option value="">Semua status</option><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
            <x-dashboard.datatable :url="route('user.datatable')" :order="[[1, 'asc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.', 'orderable' => false, 'searchable' => false],
                ['data' => 'name', 'name' => 'users.name', 'title' => 'Nama'],
                ['data' => 'email', 'name' => 'users.email', 'title' => 'Email'],
                ['data' => 'phone_number', 'name' => 'users.phone_number', 'title' => 'Telepon'],
                ['data' => 'role', 'title' => 'Role', 'orderable' => false, 'searchable' => false],
                ['data' => 'is_active', 'name' => 'users.is_active', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => '', 'orderable' => false, 'searchable' => false],
            ]" />
        </div>
    </div>
</x-dashboard.page>
