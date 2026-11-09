<x-dashboard.page title="Kepengurusan" :crumbs="['Organisasi', 'Kepengurusan']">
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Periode Kepengurusan PD & PC</div>
            @can('create', App\Models\BoardPeriod::class)
                <div class="card-toolbar">
                    <a class="btn btn-sm btn-light-primary" href="{{ route('board.create', ['organization_id' => $selectedOrganization]) }}"><i class="fa fa-plus"></i> Tambah Periode</a>
                </div>
            @endcan
        </div>
        <div class="card-body p-lg-8">
            <div class="row mb-5">
                <div class="col-md-5">
                    <select class="form-select form-select-sm" name="organization_id" data-dt-filter="datatable" aria-label="Filter organisasi">
                        <option value="">Semua organisasi</option>
                        @foreach ($organizations as $id => $name)
                            <option value="{{ $id }}" @selected((string) $selectedOrganization === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-dashboard.datatable :url="route('board.datatable')" :order="[[3, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'organization.name', 'title' => 'Organisasi'],
                ['data' => 'name', 'name' => 'board_periods.name', 'title' => 'Periode'],
                ['data' => 'starts_at', 'title' => 'Mulai', 'searchable' => false],
                ['data' => 'board_members_count', 'title' => 'Jumlah Pengurus', 'searchable' => false],
                ['data' => 'is_active', 'name' => 'board_periods.is_active', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
