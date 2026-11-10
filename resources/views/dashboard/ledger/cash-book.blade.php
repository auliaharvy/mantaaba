<x-dashboard.page title="Buku Kas & Bank" :crumbs="['Keuangan', 'Buku Kas & Bank']">
    <div class="row g-5">
        @foreach ($accounts as $row)
            <div class="col-md-6 col-xl-3">
                <a href="{{ route('ledger.index', ['account' => $row['account']->id]) }}" class="card card-flush h-100 hoverable">
                    <div class="card-body">
                        <div class="text-muted fs-7">{{ $row['account']->code }} · {{ $row['account']->is_bank ? 'Bank' : 'Kas' }}</div>
                        <div class="fw-bold fs-5 text-dark">{{ $row['account']->name }}</div>
                        <div class="fs-2 fw-bolder mt-2 {{ $row['balance'] < 0 ? 'text-danger' : 'text-dark' }}">{{ App\Support\Money::format($row['balance'] / 100) }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
    <div class="d-flex gap-2 mt-5">
        <a href="{{ route('ledger.index') }}" class="btn btn-sm btn-light"><i class="fa fa-book"></i> Buku besar akun lain</a>
        <a href="{{ route('ledger.trial-balance') }}" class="btn btn-sm btn-light"><i class="fa fa-balance-scale"></i> Neraca saldo</a>
    </div>
</x-dashboard.page>
