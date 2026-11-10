<x-dashboard.page :title="$title" :crumbs="['Keuangan', 'Laporan', $title]">
    <div class="card mb-5">
        <form method="GET" class="card-body d-flex flex-wrap gap-3 align-items-end">
            @if ($report === 'anggaran')
                <div><label class="form-label fs-7" for="year">Tahun</label><input id="year" type="number" name="year" value="{{ $year }}" class="form-control form-control-sm w-100px"></div>
            @elseif (! in_array($report, ['posisi', 'piutang', 'utang']))
                <div><label class="form-label fs-7" for="from">Dari</label><input id="from" type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
            @endif
            <div><label class="form-label fs-7" for="to">{{ in_array($report, ['posisi', 'piutang', 'utang']) ? 'Per tanggal' : 'Sampai' }}</label><input id="to" type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
            <button class="btn btn-sm btn-light-primary">Tampilkan</button>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-sm btn-light"><i class="fa fa-file-pdf"></i> PDF</a>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn btn-sm btn-light"><i class="fa fa-file-csv"></i> CSV</a>
                <a href="{{ route('report.index') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i></a>
            </div>
        </form>
    </div>
    <div class="card">
        <div class="card-body report-body">
            @include('dashboard.report.body', ['links' => true])
        </div>
    </div>
    @if ($report === 'transparansi' && auth()->user()->can('cms-create'))
        <form method="POST" action="{{ route('report.publish-transparency') }}" class="text-end mt-5" onsubmit="return confirm('Terbitkan laporan ini ke halaman Dokumen situs (publik)?')">
            @csrf <input type="hidden" name="from" value="{{ $from }}"><input type="hidden" name="to" value="{{ $to }}">
            <button class="btn btn-sm btn-primary"><i class="fa fa-globe"></i> Terbitkan ke situs</button>
        </form>
    @endif
    <x-slot name="style"><style>
        .report-body table { width: 100%; } .report-body td, .report-body th { padding: .45rem .6rem; border-bottom: 1px solid #eff2f5; }
        .report-body .n { text-align: right; white-space: nowrap; } .report-body .sub { padding-left: 1.5rem; } .report-body .total { font-weight: 700; background: #f9f9f9; }
        .report-body h3 { margin: 1.25rem 0 .5rem; font-size: 1.05rem; }
    </style></x-slot>
</x-dashboard.page>
