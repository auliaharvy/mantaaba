<x-dashboard.page title="Neraca Saldo" :crumbs="['Keuangan', 'Neraca Saldo']">
    <div class="card">
        <div class="card-header">
            <form method="GET" class="card-title d-flex gap-3 align-items-center"><label for="date" class="fs-6 fw-bold">Per tanggal</label>
                <input id="date" type="date" name="date" value="{{ $until }}" class="form-control form-control-sm w-auto" onchange="this.form.submit()"></form>
            <div class="card-toolbar"><span class="badge {{ $debit === $credit ? 'badge-light-success' : 'badge-light-danger' }} fs-6">{{ $debit === $credit ? 'Seimbang ✓' : 'TIDAK SEIMBANG' }}</span></div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-row-bordered gs-4 gy-2 mb-0 fs-7">
                    <thead><tr class="fw-bold bg-light"><th>Kode</th><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead>
                    <tbody>
                        @foreach ($rows->filter(fn ($r) => $r['debit'] || $r['credit']) as $row)
                            <tr><td>{{ $row['account']->code }}</td><td><a href="{{ route('ledger.index', ['account' => $row['account']->id, 'to' => $until]) }}">{{ $row['account']->name }}</a></td>
                                <td class="text-end">{{ $row['debit'] ? number_format($row['debit'] / 100, 0, ',', '.') : '' }}</td><td class="text-end">{{ $row['credit'] ? number_format($row['credit'] / 100, 0, ',', '.') : '' }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="fw-bolder bg-light"><td colspan="2">Total</td><td class="text-end">{{ number_format($debit / 100, 0, ',', '.') }}</td><td class="text-end">{{ number_format($credit / 100, 0, ',', '.') }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</x-dashboard.page>
