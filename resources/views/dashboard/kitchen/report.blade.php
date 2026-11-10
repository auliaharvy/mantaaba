@php
    $rp = fn ($cents) => App\Support\Money::format(App\Support\Money::decimal($cents));
    $budget = $r['days']->first()['budget'] ?? null;
@endphp
<x-dashboard.page title="Laporan Dapur Bulanan" :crumbs="['Operasional', 'Dapur', 'Laporan']">
    @include('dashboard.kitchen.nav')
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-5 align-items-center">
            <form method="GET"><input type="month" name="bulan" value="{{ $month->format('Y-m') }}" class="form-control form-control-sm" onchange="this.form.submit()" aria-label="Bulan"></form>
            <div><div class="text-muted fs-8">Total biaya bahan</div><b class="fs-4">{{ $rp($r['cost']) }}</b></div>
            <div><div class="text-muted fs-8">Rata-rata per orang per hari</div><b class="fs-4 {{ $budget && $r['avg_per_person'] > $budget ? 'text-danger' : '' }}">{{ $r['avg_per_person'] !== null ? $rp($r['avg_per_person']) : '-' }}</b>@if ($budget)<span class="text-muted fs-8"> / anggaran {{ $rp($budget) }}</span>@endif</div>
            <div><div class="text-muted fs-8">Sisa makanan</div><b class="fs-4">{{ $r['leftover_percent'] !== null ? $r['leftover_percent'] . '%' : '-' }}</b></div>
            <div><div class="text-muted fs-8">Kontribusi kebun</div><b class="fs-4">{{ $rp($r['garden']) }}</b></div>
        </div>
    </div>
    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Biaya per hari</div></div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0 fs-7">
                        <thead><tr class="fw-bold bg-light"><th>Tanggal</th><th class="text-end">Biaya</th><th class="text-end">Per orang</th><th class="text-end">Porsi (sisa)</th><th class="text-end">Kebun</th></tr></thead>
                        <tbody>
                            @foreach ($r['days'] as $d)
                                @continue(! $d['cost'] && ! $d['garden'])
                                <tr><td>{{ $d['date']->translatedFormat('D, j M') }}</td><td class="text-end">{{ $rp($d['cost']) }}</td>
                                    <td class="text-end {{ $d['budget'] && $d['per_person'] > $d['budget'] ? 'text-danger fw-bold' : '' }}">{{ $d['per_person'] !== null ? $rp($d['per_person']) : '-' }}</td>
                                    <td class="text-end">{{ $d['portions'] }} ({{ $d['leftover'] }})</td><td class="text-end">{{ $rp($d['garden']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Menu terbanyak</div></div>
                <div class="card-body">@forelse ($r['top_menus'] as $m)<div class="d-flex justify-content-between py-1"><span>{{ $m['menu']->name }}</span><b>{{ $m['count'] }}×</b></div>@empty<span class="text-muted">-</span>@endforelse</div>
            </div>
            <div class="card">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Bahan termahal</div></div>
                <div class="card-body">@forelse ($r['top_ingredients'] as $i)<div class="d-flex justify-content-between py-1"><span>{{ $i['item']->name }} <span class="text-muted fs-8">{{ App\Services\StockLedger::qty($i['qty']) }} {{ $i['item']->unit }}</span></span><b>{{ $rp($i['cost']) }}</b></div>@empty<span class="text-muted">-</span>@endforelse</div>
            </div>
        </div>
    </div>
    <p class="text-muted fs-8 mt-4">Biaya per orang = biaya bahan hari itu ÷ penghuni aktif. Kontribusi kebun = panen ke dapur × harga pasar (nilai akuntansinya Rp 0). Pemakaian bahan dijurnal sekali sebulan saat tutup periode.</p>
</x-dashboard.page>
