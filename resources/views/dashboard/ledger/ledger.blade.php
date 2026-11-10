<x-dashboard.page title="Buku Besar" :crumbs="['Keuangan', 'Buku Besar']">
    <div class="card mb-5">
        <form method="GET" class="card-body d-flex flex-wrap gap-3 align-items-end">
            <div class="flex-grow-1" style="min-width: 240px"><label class="form-label fs-7" for="account">Akun</label>
                <select id="account" name="account" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">— Pilih akun —</option>@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($account?->id === $a->id)>{{ $a->code }} {{ $a->name }}</option>@endforeach</select></div>
            <div><label class="form-label fs-7" for="from">Dari</label><input id="from" type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
            <div><label class="form-label fs-7" for="to">Sampai</label><input id="to" type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
            <button class="btn btn-sm btn-light-primary">Tampilkan</button>
        </form>
    </div>
    @if ($ledger)
        @php($sign = $account->normal_balance === 'debit' ? 1 : -1)
        <div class="card">
            <div class="card-header"><div class="card-title fs-5 fw-bolder">{{ $account->label() }} <span class="text-muted fs-7 ms-2">saldo normal {{ $account->normal_balance }}</span></div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered gs-4 gy-2 mb-0 fs-7">
                        <thead><tr class="fw-bold bg-light"><th>Tanggal</th><th>Voucher</th><th>Uraian</th><th>Pusat biaya</th><th class="text-end">Debit</th><th class="text-end">Kredit</th><th class="text-end">Saldo</th></tr></thead>
                        <tbody>
                            <tr class="fw-bold"><td colspan="6">Saldo awal</td><td class="text-end">{{ number_format($sign * $ledger['opening'] / 100, 0, ',', '.') }}</td></tr>
                            @foreach ($ledger['rows'] as $row)
                                @php($l = $row['line'])
                                <tr><td class="text-nowrap">{{ $l->journal->date->translatedFormat('d M Y') }}</td><td><a href="{{ route('journal.show', $l->journal_id) }}">{{ $l->journal->number }}</a></td>
                                    <td>{{ $l->journal->description }}{{ $l->party ? ' · ' . $l->party->name : '' }}{{ $l->memo ? ' · ' . $l->memo : '' }}</td><td>{{ $l->costCenter?->code }}</td>
                                    <td class="text-end">{{ (float) $l->debit ? number_format($l->debit, 0, ',', '.') : '' }}</td><td class="text-end">{{ (float) $l->credit ? number_format($l->credit, 0, ',', '.') : '' }}</td>
                                    <td class="text-end">{{ number_format($sign * $row['balance'] / 100, 0, ',', '.') }}</td></tr>
                            @endforeach
                            <tr class="fw-bolder bg-light"><td colspan="6">Saldo akhir</td><td class="text-end">{{ number_format($sign * $ledger['closing'] / 100, 0, ',', '.') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-dashboard.page>
