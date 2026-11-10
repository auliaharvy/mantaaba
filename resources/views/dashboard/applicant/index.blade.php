<x-dashboard.page title="Pendaftar" :crumbs="['Penerimaan', 'Pendaftar']">
    @if (! $batch)
        <div class="card"><div class="card-body text-muted">Belum ada angkatan. @can('batch-create')<a href="{{ route('batch.create') }}">Buat angkatan</a>@endcan</div></div>
    @else
        <div class="card">
            <div class="card-header flex-wrap gap-3 py-4">
                <div class="card-title fs-4 fw-bolder">Pendaftar · {{ $batch->name }} <span class="text-muted fs-7 ms-2">kuota {{ $batch->quota }} · diterima {{ $counts['diterima'] ?? 0 }}</span></div>
                <div class="card-toolbar gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <select name="batch" class="form-select form-select-sm w-auto" onchange="this.form.submit()" aria-label="Angkatan">
                            @foreach ($batches as $b)<option value="{{ $b->id }}" @selected($b->id === $batch->id)>{{ $b->code }}</option>@endforeach
                        </select>
                    </form>
                    @can('admission-export')<a href="{{ route('applicant.export', ['batch' => $batch->id]) }}" class="btn btn-sm btn-light-primary"><i class="fa fa-file-csv"></i> Export CSV</a>@endcan
                </div>
            </div>
            <div class="card-body p-lg-8">
                <input type="hidden" name="batch" value="{{ $batch->id }}" data-dt-filter="datatable">
                <ul class="nav nav-pills mb-5 gap-1" id="status-tabs">
                    @foreach (App\Models\Applicant::STATUSES as $key => $label)
                        <li class="nav-item"><a href="#" data-status="{{ $key }}" class="nav-link btn btn-sm btn-active-light-primary py-2 {{ $status === $key ? 'active' : '' }}">{{ $label }} <span class="badge badge-light ms-1">{{ $counts[$key] ?? 0 }}</span></a></li>
                    @endforeach
                </ul>
                <input type="hidden" name="status" id="status-filter" value="{{ $status }}" data-dt-filter="datatable">
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <select name="priority" class="form-select form-select-sm w-auto" data-dt-filter="datatable" aria-label="Filter prioritas"><option value="">Semua pendaftar</option><option value="1">Prioritas ★</option></select>
                    <select name="district" class="form-select form-select-sm w-auto" data-dt-filter="datatable" aria-label="Filter kecamatan"><option value="">Semua kecamatan</option>@foreach ($districts as $d)<option>{{ $d }}</option>@endforeach</select>
                </div>
                <x-dashboard.datatable :url="route('applicant.datatable')" :order="[[1, 'asc']]" :columns="[
                    ['data' => 'DT_RowIndex', 'title' => 'No.'],
                    ['data' => 'registration_number', 'title' => 'Nomor'],
                    ['data' => 'name', 'title' => 'Nama'],
                    ['data' => 'district', 'title' => 'Kecamatan'],
                    ['data' => 'family_status', 'title' => 'Keluarga', 'searchable' => false],
                    ['data' => 'score', 'title' => 'Skor', 'orderable' => false, 'searchable' => false],
                    ['data' => 'created_at', 'title' => 'Daftar', 'searchable' => false],
                    ['data' => 'action', 'title' => ''],
                ]" />
            </div>
        </div>
        <x-slot name="javascript">
            <script>
                document.querySelectorAll('#status-tabs [data-status]').forEach((a) => a.addEventListener('click', (e) => {
                    e.preventDefault();
                    document.querySelectorAll('#status-tabs .nav-link').forEach((x) => x.classList.remove('active'));
                    a.classList.add('active');
                    $('#status-filter').val(a.dataset.status).trigger('change');
                }));
            </script>
        </x-slot>
    @endif
</x-dashboard.page>
