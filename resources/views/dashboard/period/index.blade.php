<x-dashboard.page title="Periode Buku" :crumbs="['Keuangan', 'Periode']">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <form method="GET" class="d-flex gap-2 align-items-center"><label for="tahun" class="fw-bold">Tahun</label>
                <input id="tahun" type="number" name="tahun" value="{{ $year }}" class="form-control form-control-sm w-100px" onchange="this.form.submit()"></form>
            @can('period-close')<a href="{{ route('opening.show') }}" class="btn btn-sm btn-light-primary ms-auto"><i class="fa fa-flag-checkered"></i> Saldo awal</a>@endcan
        </div>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0">
                <thead><tr class="fw-bold bg-light fs-7"><th>Periode</th><th>Status</th><th>Voucher belum diposting</th><th>Keterangan</th><th></th></tr></thead>
                <tbody>
                    @foreach ($periods as $p)
                        <tr>
                            <td class="fw-bold">{{ $p->label() }}</td>
                            <td><span class="badge {{ $p->isClosed() ? 'badge-light-dark' : 'badge-light-success' }}">{{ $p->isClosed() ? 'Ditutup' : 'Terbuka' }}</span></td>
                            <td>{{ $pending[$p->id] ?? 0 }}</td>
                            <td class="fs-8 text-muted">{{ $p->closed_at ? 'Ditutup ' . $p->closed_at->translatedFormat('j M Y') : '' }}{{ $p->reopen_reason ? ' · dibuka ulang: ' . $p->reopen_reason : '' }}</td>
                            <td class="text-end">
                                @if (! $p->isClosed())
                                    @can('period-close')<form method="POST" action="{{ route('period.close', $p) }}" onsubmit="return confirm('Tutup periode {{ $p->label() }}?{{ ($pending[$p->id] ?? 0) ? ' Masih ada ' . $pending[$p->id] . ' voucher belum diposting; voucher tersebut tidak bisa diposting ke periode tertutup.' : '' }}')">@csrf<button class="btn btn-sm btn-light-dark">Tutup</button></form>@endcan
                                @elseif (auth()->user()->isSuperAdmin())
                                    <form method="POST" action="{{ route('period.reopen', $p) }}" onsubmit="const r = prompt('Alasan membuka ulang {{ $p->label() }}:'); if (!r) return false; this.reason.value = r;">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-light-warning">Buka ulang</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-muted fs-7 mt-4">Ceklis tutup bulan (jurnal otomatis, rekonsiliasi kas/bank) dan tutup buku tahunan ditambahkan bersama laporan keuangan.</p>
</x-dashboard.page>
