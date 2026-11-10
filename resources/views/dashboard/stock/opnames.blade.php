<x-dashboard.page title="Stock Opname" :crumbs="['Operasional', 'Stock Opname']">
    @can('stock-opname')
        <form method="POST" action="{{ route('stock.opname-start') }}" class="card mb-5">
            @csrf
            <div class="card-body d-flex flex-wrap gap-3 align-items-end">
                <div><label class="form-label fs-7" for="op-wh">Gudang</label><select id="op-wh" name="warehouse_id" class="form-select form-select-sm" required>@foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select></div>
                <div><label class="form-label fs-7" for="op-date">Tanggal</label><input id="op-date" type="date" name="date" value="{{ today()->toDateString() }}" class="form-control form-control-sm" required></div>
                <button class="btn btn-sm btn-primary">Mulai opname</button>
            </div>
        </form>
    @endcan
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0">
                <thead><tr class="fw-bold bg-light fs-7"><th>Tanggal</th><th>Gudang</th><th>Status</th><th>Barang berselisih</th><th></th></tr></thead>
                <tbody>
                    @forelse ($opnames as $o)
                        <tr>
                            <td>{{ $o->date->format('d/m/Y') }}</td><td>{{ $o->warehouse->name }}</td>
                            <td><span class="badge {{ $o->status === 'final' ? 'badge-light-success' : 'badge-light-warning' }}">{{ $o->status === 'final' ? 'Final' : 'Draf' }}</span></td>
                            <td>{{ collect($o->lines)->filter(fn ($l) => $l['physical'] !== null && round($l['physical'] - $l['system'], 3) != 0)->count() }}</td>
                            <td class="text-end"><a href="{{ route('stock.opname', $o) }}" class="btn btn-sm btn-light-primary">{{ $o->status === 'final' ? 'Lihat' : 'Lanjutkan' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-10">Belum ada opname.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $opnames->links() }}</div>
</x-dashboard.page>
