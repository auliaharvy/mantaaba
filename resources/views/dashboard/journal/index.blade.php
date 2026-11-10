<x-dashboard.page title="Voucher" :crumbs="['Keuangan', 'Voucher']">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            @can('journal-create')
                <a href="{{ route('journal.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Voucher baru</a>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" data-bs-toggle="dropdown" type="button">Template cepat</button>
                    <ul class="dropdown-menu">
                        @foreach (config('mantaaba.voucher_templates') as $key => $t)<li><a class="dropdown-item" href="{{ route('journal.create', ['template' => $key]) }}">{{ $t['label'] }} <span class="text-muted fs-8">{{ $t['type'] }}</span></a></li>@endforeach
                    </ul>
                </div>
            @endcan
            @can('journal-approve')<a href="{{ route('journal.queue') }}" class="btn btn-sm btn-light-warning ms-auto">Antrean persetujuan <span class="badge badge-warning ms-1">{{ $counts['diajukan'] ?? 0 }}</span></a>@endcan
        </div>
    </div>
    <div class="card">
        <div class="card-header flex-wrap gap-3 py-4">
            <ul class="nav nav-pills gap-1" id="status-tabs">
                <li class="nav-item"><a href="#" data-status="" class="nav-link btn btn-sm btn-active-light-primary py-2 {{ $status === '' ? 'active' : '' }}">Semua</a></li>
                @foreach (App\Models\Journal::STATUSES as $key => $label)
                    <li class="nav-item"><a href="#" data-status="{{ $key }}" class="nav-link btn btn-sm btn-active-light-primary py-2 {{ $status === $key ? 'active' : '' }}">{{ $label }} <span class="badge badge-light ms-1">{{ $counts[$key] ?? 0 }}</span></a></li>
                @endforeach
            </ul>
            <div class="d-flex flex-wrap gap-2">
                <input type="hidden" name="status" id="status-filter" value="{{ $status }}" data-dt-filter="datatable">
                <select name="type" class="form-select form-select-sm w-auto" data-dt-filter="datatable" aria-label="Jenis"><option value="">Semua jenis</option>@foreach (App\Models\Journal::TYPES as $k => $l)<option value="{{ $k }}">{{ $k }}</option>@endforeach</select>
                <input type="date" name="from" class="form-control form-control-sm w-auto" data-dt-filter="datatable" aria-label="Dari tanggal">
                <input type="date" name="to" class="form-control form-control-sm w-auto" data-dt-filter="datatable" aria-label="Sampai tanggal">
            </div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('journal.datatable')" :order="[[2, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'number', 'title' => 'Nomor'],
                ['data' => 'date', 'title' => 'Tanggal', 'searchable' => false],
                ['data' => 'voucher_type', 'title' => 'Jenis'],
                ['data' => 'description', 'title' => 'Uraian'],
                ['data' => 'amount', 'title' => 'Jumlah', 'orderable' => false, 'searchable' => false, 'class' => 'text-end'],
                ['data' => 'status', 'title' => 'Status', 'searchable' => false],
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
</x-dashboard.page>
