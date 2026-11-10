@php
    $buy = $rows->filter(fn ($r) => $r['buy'] > 0);
    $text = "Daftar belanja dapur {$start->translatedFormat('j M')}–{$start->copy()->addDays(6)->translatedFormat('j M')}:\n" . $buy->map(fn ($r) => '- ' . $r['item']->name . ' ' . App\Services\StockLedger::qty($r['buy']) . ' ' . $r['item']->unit)->implode("\n");
@endphp
<x-dashboard.page title="Daftar Belanja" :crumbs="['Operasional', 'Dapur', 'Kebutuhan']">
    @include('dashboard.kitchen.nav')
    <div class="card">
        <div class="card-header flex-wrap py-3">
            <div class="card-title flex-column align-items-start"><span class="fs-5 fw-bolder">Kebutuhan {{ $start->translatedFormat('j M') }} – {{ $start->copy()->addDays(6)->translatedFormat('j M Y') }}</span>
                <span class="text-muted fs-8">Dari rencana yang belum dimasak, dikurangi stok {{ $warehouse->name }}</span></div>
            <div class="card-toolbar gap-2 d-print-none">
                <form method="GET"><input type="date" name="minggu" value="{{ $start->toDateString() }}" class="form-control form-control-sm" onchange="this.form.submit()" aria-label="Minggu"></form>
                <button type="button" class="btn btn-sm btn-light" onclick="window.print()"><i class="fa fa-print"></i> Cetak</button>
                @if ($buy->isNotEmpty())
                    <a href="https://wa.me/?text={{ rawurlencode($text) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light-success"><i class="fab fa-whatsapp"></i> Kirim WA</a>
                    @can('stock-move')<a href="{{ route('stock.purchase-form', ['gudang' => $warehouse->id, 'items' => $buy->mapWithKeys(fn ($r) => [$r['item']->id => $r['buy']])->all()]) }}" class="btn btn-sm btn-primary">Catat pembelian</a>@endcan
                @endif
            </div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0">
                <thead><tr class="fw-bold bg-light fs-7"><th>Bahan</th><th class="text-end">Kebutuhan</th><th class="text-end">Stok</th><th class="text-end">Beli</th></tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr><td>{{ $r['item']->name }}</td><td class="text-end">{{ App\Services\StockLedger::qty($r['need']) }} {{ $r['item']->unit }}</td>
                            <td class="text-end">{{ App\Services\StockLedger::qty($r['stock']) }}</td><td class="text-end fw-bold {{ $r['buy'] > 0 ? 'text-danger' : 'text-success' }}">{{ $r['buy'] > 0 ? App\Services\StockLedger::qty($r['buy']) . ' ' . $r['item']->unit : 'cukup' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-10">Belum ada rencana menu minggu ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-dashboard.page>
