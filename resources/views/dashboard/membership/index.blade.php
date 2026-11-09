<x-dashboard.page title="Data Anggota" :crumbs="['Organisasi', 'Data Anggota']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Daftar Anggota</div>
            <div class="card-toolbar gap-2 flex-wrap">
                <a class="btn btn-sm btn-light" id="export-link" href="{{ route('membership.export') }}"><i class="fa fa-download"></i> Export CSV</a>
                <a class="btn btn-sm btn-light" href="{{ route('membership.ppl-export', ['tahun' => now()->year]) }}"><i class="fa fa-clock"></i> Rekap PPL {{ now()->year }}</a>
                @can('create', App\Models\Member::class)
                    <a class="btn btn-sm btn-light" href="{{ route('membership.import.form') }}"><i class="fa fa-upload"></i> Import CSV</a>
                    <a class="btn btn-sm btn-light-primary" href="{{ route('membership.create') }}"><i class="fa fa-plus"></i> Tambah Anggota</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-lg-8">
            <div class="row g-3 mb-5" id="member-filters">
                <div class="col-sm-6 col-lg-3">
                    <select class="form-select form-select-sm" name="organization_id" data-dt-filter="datatable" aria-label="Filter PC">
                        <option value="">Semua PC</option>
                        @foreach ($organizations as $id => $name)
                            <option value="{{ $id }}" @selected((string) $selectedOrganization === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <select class="form-select form-select-sm" name="membership_type" data-dt-filter="datatable" aria-label="Filter jenis">
                        <option value="">Semua jenis</option>
                        @foreach (App\Models\Member::TYPES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <select class="form-select form-select-sm" name="status" data-dt-filter="datatable" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach (App\Models\Member::STATUSES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <select class="form-select form-select-sm" name="dues_status" data-dt-filter="datatable" aria-label="Filter status iuran">
                        <option value="">Semua status iuran</option>
                        @foreach (App\Models\Member::DUES_STATUSES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
            </div>
            <x-dashboard.datatable :url="route('membership.datatable')" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'member_number', 'title' => 'No. Anggota'],
                ['data' => 'user.name', 'title' => 'Nama'],
                ['data' => 'user.email', 'title' => 'Email'],
                ['data' => 'organization.name', 'title' => 'PC'],
                ['data' => 'membership_type', 'title' => 'Jenis', 'searchable' => false],
                ['data' => 'status', 'name' => 'members.status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'dues_status', 'title' => 'Iuran ' . now()->year, 'orderable' => false, 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>

    <x-slot name="javascript">
        <script>
            // export mengikuti filter yang sedang aktif
            $('#export-link').on('click', function () {
                const params = $('#member-filters [name]').serialize();
                this.href = '{{ route('membership.export') }}?' + params;
            });
        </script>
    </x-slot>
</x-dashboard.page>
