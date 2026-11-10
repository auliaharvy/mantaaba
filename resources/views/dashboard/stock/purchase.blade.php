@php
    $rows = old('lines', $prefill ?: [[]]);
    $rows = array_pad($rows, max(count($rows), 5), []);
@endphp
<x-dashboard.page title="Catat Pembelian" :crumbs="['Operasional', 'Pembelian', 'Baru']">
    <form method="POST" action="{{ route('stock.purchase') }}" enctype="multipart/form-data" class="card">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><x-dashboard.field name="date" label="Tanggal" type="date" required :value="today()->toDateString()" /></div>
                <div class="col-md-3"><x-dashboard.field name="warehouse_id" label="Masuk ke gudang" type="select" required :value="$warehouseId" :options="$warehouses->pluck('name', 'id')->all()" /></div>
                <div class="col-md-3"><x-dashboard.field name="payment" label="Cara bayar" type="select" required value="tunai" :options="collect(App\Services\StockPurchase::PAYMENTS)->map(fn ($p) => $p[0] . ' (' . $p[1] . ')')->all()" /></div>
                <div class="col-md-3"><x-dashboard.field name="party_id" label="Toko / pemasok" type="select" :options="$parties->pluck('name', 'id')->all()" help="Wajib untuk kredit." /></div>
                <div class="col-md-3"><x-dashboard.field name="reference_no" label="No. nota" /></div>
                <div class="col-md-5"><x-dashboard.field name="receipt" label="Foto/scan nota" type="file" required accept="image/*,application/pdf" capture="environment" /></div>
            </div>
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gy-2" id="purchase-lines">
                    <thead><tr class="fw-bold fs-7"><th class="min-w-200px">Barang</th><th class="w-125px">Jumlah</th><th class="w-150px">Harga satuan (Rp)</th><th class="text-end w-150px">Subtotal</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $i => $row)
                            <tr>
                                <td><select name="lines[{{ $i }}][item_id]" class="form-select form-select-sm" aria-label="Barang baris {{ $i + 1 }}"><option value="">—</option>@foreach ($items as $it)<option value="{{ $it->id }}" @selected((int) ($row['item_id'] ?? 0) === $it->id)>{{ $it->label() }}</option>@endforeach</select></td>
                                <td><input type="number" step="0.001" min="0" name="lines[{{ $i }}][qty]" value="{{ $row['qty'] ?? '' }}" class="form-control form-control-sm" aria-label="Jumlah baris {{ $i + 1 }}"></td>
                                <td><input type="number" step="1" min="0" name="lines[{{ $i }}][unit_price]" value="{{ $row['unit_price'] ?? '' }}" class="form-control form-control-sm" aria-label="Harga baris {{ $i + 1 }}"></td>
                                <td class="text-end subtotal">0</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bolder" id="purchase-total">0</td></tr></tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap gap-3 justify-content-end align-items-center">
            <span class="text-muted fs-7 me-auto">Stok langsung bertambah; voucher pembelian diajukan ke Bendahara untuk disetujui.</span>
            <a href="{{ route('stock.purchases') }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-primary">Simpan pembelian</button>
        </div>
    </form>
    <x-slot name="javascript"><script>
        (function () {
            const table = document.getElementById('purchase-lines');
            const fmt = (n) => new Intl.NumberFormat('id-ID').format(Math.round(n));
            const recalc = () => {
                let total = 0;
                table.querySelectorAll('tbody tr').forEach((tr) => {
                    const [qty, price] = tr.querySelectorAll('input');
                    const sub = (parseFloat(qty.value) || 0) * (parseFloat(price.value) || 0);
                    tr.querySelector('.subtotal').textContent = fmt(sub);
                    total += sub;
                });
                document.getElementById('purchase-total').textContent = fmt(total);
            };
            table.addEventListener('input', recalc);
            recalc();
        })();
    </script></x-slot>
</x-dashboard.page>
