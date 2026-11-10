<x-dashboard.page title="Donasi" :crumbs="['Keuangan', 'Donasi']">
    <div class="row g-5 mb-5">
        <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted fs-7">Menunggu verifikasi</div><div class="fs-2 fw-bolder {{ $waiting ? 'text-warning' : '' }}">{{ $waiting }}</div></div></div></div>
        @foreach (App\Models\Donation::PURPOSES as $key => [$label])
            @continue(! isset($recap[$key]))
            <div class="col-6 col-md-3"><div class="card h-100"><div class="card-body"><div class="text-muted fs-7">{{ $label }} {{ $year }} ({{ $recap[$key]['count'] }})</div><div class="fs-4 fw-bolder">{{ App\Support\Money::format(App\Support\Money::decimal($recap[$key]['amount'])) }}</div></div></div></div>
        @endforeach
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title fs-4 fw-bolder">Daftar donasi</div>
            <div class="card-toolbar gap-2">
                <select class="form-select form-select-sm w-auto" name="status" data-dt-filter="datatable" aria-label="Filter status"><option value="">Semua status</option>@foreach (App\Models\Donation::STATUSES as $v => $l)<option value="{{ $v }}" @selected($v === 'menunggu' && $waiting)>{{ $l }}</option>@endforeach</select>
                @can('donation-create')<a href="{{ route('donation.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Catat donasi</a>@endcan
            </div>
        </div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('donation.datatable')" :order="[[3, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'donor_name', 'title' => 'Donatur'],
                ['data' => 'amount', 'title' => 'Jumlah', 'searchable' => false],
                ['data' => 'transferred_at', 'title' => 'Tanggal', 'searchable' => false],
                ['data' => 'purpose', 'title' => 'Peruntukan', 'searchable' => false],
                ['data' => 'status', 'title' => 'Status', 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
