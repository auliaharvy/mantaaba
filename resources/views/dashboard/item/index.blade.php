<x-dashboard.page title="Stok & Barang" :crumbs="['Operasional', 'Stok & Barang']">
    <div class="card">
        <div class="card-header flex-wrap py-3 gap-2">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-200px" placeholder="Cari barang" aria-label="Cari barang">
                <select name="kategori" class="form-select form-select-sm w-auto" onchange="this.form.submit()" aria-label="Filter kategori">
                    <option value="">Semua kategori</option>
                    @foreach (config('mantaaba.item_categories') as $key => [$label])<option value="{{ $key }}" @selected(request('kategori') === $key)>{{ $label }}</option>@endforeach
                </select>
            </form>
            <div class="card-toolbar gap-2">
                @can('stock-move')<a href="{{ route('stock.move-form') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-exchange-alt"></i> Mutasi</a>
                    <a href="{{ route('stock.purchase-form') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-shopping-basket"></i> Pembelian</a>@endcan
                @can('stock-create')<a href="{{ route('item.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Barang</a>@endcan
            </div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0">
                <thead><tr class="fw-bold bg-light fs-7"><th>Barang</th><th>Kategori</th>@foreach ($warehouses as $w)<th class="text-end">{{ $w->name }}</th>@endforeach<th class="text-end">Total</th><th class="text-end">Nilai rata-rata</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $total = $balances->sum(fn ($b) => $b[$item->id] ?? 0);
                            $low = (float) $item->min_stock > 0 && $total < (float) $item->min_stock;
                        @endphp
                        <tr class="{{ $item->is_active ? '' : 'text-muted' }}">
                            <td><a href="{{ route('item.show', $item) }}" class="fw-bold">{{ $item->name }}</a> <span class="text-muted fs-8">{{ $item->code }}</span>
                                @if ($low)<span class="badge badge-light-danger ms-1">menipis</span>@endif</td>
                            <td class="fs-7">{{ $item->categoryLabel() }}</td>
                            @foreach ($warehouses as $w)<td class="text-end">{{ App\Services\StockLedger::qty($balances[$w->id][$item->id] ?? 0) }}</td>@endforeach
                            <td class="text-end fw-bold">{{ App\Services\StockLedger::qty($total) }} {{ $item->unit }}</td>
                            <td class="text-end fs-7">{{ App\Support\Money::format($item->avg_cost) }}/{{ $item->unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 4 + $warehouses->count() }}" class="text-center text-muted py-10">Belum ada barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-dashboard.page>
