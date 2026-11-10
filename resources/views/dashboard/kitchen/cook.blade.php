<x-dashboard.page :title="'Masak · ' . App\Models\MealPlan::MEALS[$plan->meal]" :crumbs="['Operasional', 'Dapur', 'Masak']">
    <form method="POST" action="{{ route('kitchen.cook', $plan) }}" enctype="multipart/form-data" class="card mw-800px">
        @csrf
        <div class="card-header"><div class="card-title flex-column align-items-start"><span class="fs-5 fw-bolder">{{ $plan->date->translatedFormat('l, j M Y') }} · {{ $menus->pluck('name')->implode(', ') }}</span>
            <span class="text-muted fs-8">Bahan terisi dari resep × {{ $plan->planned_portions }} porsi rencana; sesuaikan dengan yang benar-benar dipakai.</span></div></div>
        <div class="card-body">
            <table class="table table-row-bordered align-middle gy-2 fs-7">
                <thead><tr class="fw-bold"><th>Bahan</th><th class="text-end">Stok Gudang Dapur</th><th class="w-150px">Dipakai</th></tr></thead>
                <tbody>
                    @forelse ($needs as $itemId => $qty)
                        @php($item = $items->firstWhere('id', $itemId) ?? App\Models\Item::find($itemId))
                        <tr class="{{ ($stock[$itemId] ?? 0) < $qty ? 'text-danger' : '' }}">
                            <td>{{ $item->name }}</td><td class="text-end">{{ App\Services\StockLedger::qty($stock[$itemId] ?? 0) }} {{ $item->unit }}</td>
                            <td><input type="number" step="0.001" min="0" name="ingredients[{{ $itemId }}]" value="{{ old('ingredients.' . $itemId, $qty) }}" class="form-control form-control-sm" aria-label="Dipakai {{ $item->name }}"></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">Resep belum punya bahan; tambahkan bahan di bawah.</td></tr>
                    @endforelse
                    <tr>
                        <td colspan="2"><select name="extra_item_id" class="form-select form-select-sm" aria-label="Bahan tambahan"><option value="">+ Bahan tambahan</option>@foreach ($items as $it)<option value="{{ $it->id }}">{{ $it->label() }} · stok {{ App\Services\StockLedger::qty($stock[$it->id] ?? 0) }}</option>@endforeach</select></td>
                        <td><input type="number" step="0.001" min="0" name="extra_qty" value="{{ old('extra_qty') }}" class="form-control form-control-sm" aria-label="Jumlah bahan tambahan"></td>
                    </tr>
                </tbody>
            </table>
            <div class="row">
                <div class="col-md-4"><x-dashboard.field name="actual_portions" label="Porsi dimasak" type="number" min="1" required :value="$portions" /></div>
                <div class="col-md-4"><x-dashboard.field name="leftover_portions" label="Porsi sisa" type="number" min="0" value="0" /></div>
                <div class="col-md-4"><x-dashboard.field name="photo" label="Foto" type="file" accept="image/*" capture="environment" /></div>
                <div class="col-12"><x-dashboard.field name="note" label="Catatan" /></div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('kitchen.index', ['tanggal' => $plan->date->toDateString()]) }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-primary">Simpan masakan</button>
        </div>
    </form>
</x-dashboard.page>
