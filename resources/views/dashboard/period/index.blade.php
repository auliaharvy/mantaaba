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
                            <td class="fw-bold"><a href="{{ route('period.show', $p) }}">{{ $p->label() }}</a></td>
                            <td><span class="badge {{ $p->isClosed() ? 'badge-light-dark' : 'badge-light-success' }}">{{ $p->isClosed() ? 'Ditutup' : 'Terbuka' }}</span></td>
                            <td>{{ $pending[$p->id] ?? 0 }}</td>
                            <td class="fs-8 text-muted">{{ $p->closed_at ? 'Ditutup ' . $p->closed_at->translatedFormat('j M Y') : '' }}{{ $p->reopen_reason ? ' · dibuka ulang: ' . $p->reopen_reason : '' }}</td>
                            <td class="text-end">
                                @if (! $p->isClosed())
                                    <a href="{{ route('period.show', $p) }}" class="btn btn-sm btn-light-dark">Ceklis tutup bulan</a>
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
    @can('period-close')
        <form method="POST" action="{{ route('period.close-year') }}" class="card mt-5" onsubmit="return confirm('Tutup buku {{ $year }}? Pendapatan & beban setahun dipindah ke aset neto dengan jurnal penutup bertanggal 31 Desember.')">
            @csrf <input type="hidden" name="year" value="{{ $year }}">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                <div><b>Tutup buku tahunan {{ $year }}</b><div class="text-muted fs-7">Syarat: Januari–November sudah ditutup. Setelah itu tutup periode Desember.</div></div>
                <button class="btn btn-sm btn-dark ms-auto"><i class="fa fa-book"></i> Tutup buku {{ $year }}</button>
            </div>
        </form>
    @endcan
</x-dashboard.page>
