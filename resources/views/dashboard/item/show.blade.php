<x-dashboard.page :title="$item->name" :crumbs="['Operasional', 'Barang', $item->code]">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-5 align-items-center">
            <div><div class="text-muted fs-7">Kategori</div><b>{{ $item->categoryLabel() }}</b></div>
            <div><div class="text-muted fs-7">Saldo</div><b class="fs-3">{{ App\Services\StockLedger::qty($moves->first()->running ?? 0) }} {{ $item->unit }}</b>@if ($warehouseId)<span class="text-muted fs-8"> (gudang terpilih)</span>@endif</div>
            <div><div class="text-muted fs-7">Rata-rata tertimbang</div><b>{{ App\Support\Money::format($item->avg_cost) }}/{{ $item->unit }}</b></div>
            <div><div class="text-muted fs-7">Stok minimum</div><b>{{ App\Services\StockLedger::qty((float) $item->min_stock) }}</b></div>
            <div class="ms-auto d-flex flex-wrap gap-2">
                <form method="GET"><select name="gudang" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Filter gudang"><option value="">Semua gudang</option>@foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected($warehouseId === $w->id)>{{ $w->name }}</option>@endforeach</select></form>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn btn-sm btn-light"><i class="fa fa-file-csv"></i> CSV</a>
                @can('stock-update')<a href="{{ route('item.edit', $item) }}" class="btn btn-sm btn-light-success"><i class="fa fa-pen"></i></a>@endcan
                <a href="{{ route('item.index') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i></a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title fs-5 fw-bolder">Kartu stok</div></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0 fs-7">
                <thead><tr class="fw-bold bg-light"><th>Tanggal</th><th>Gudang</th><th>Mutasi</th><th class="text-end">Qty</th><th class="text-end">Nilai</th><th class="text-end">Saldo</th><th>Catatan</th></tr></thead>
                <tbody>
                    @forelse ($moves as $m)
                        <tr>
                            <td>{{ $m->date->format('d/m/Y') }}</td>
                            <td>{{ $m->warehouse->name }}</td>
                            <td>{{ $m->typeLabel() }}@if ($m->journal)<div><a href="{{ route('journal.show', $m->journal) }}" class="fs-8">{{ $m->journal->displayNumber() }}</a></div>@endif</td>
                            <td class="text-end {{ $m->qty < 0 ? 'text-danger' : 'text-success' }}">{{ $m->qty > 0 ? '+' : '' }}{{ App\Services\StockLedger::qty((float) $m->qty) }}</td>
                            <td class="text-end">{{ App\Support\Money::format($m->total_cost) }}</td>
                            <td class="text-end fw-bold">{{ App\Services\StockLedger::qty($m->running) }}</td>
                            <td>{{ $m->note }}<div class="text-muted fs-8">{{ $m->creator?->name }}</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-10">Belum ada mutasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-dashboard.page>
