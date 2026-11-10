<x-dashboard.page title="Mutasi Stok" :crumbs="['Operasional', 'Stok', 'Mutasi']">
    <form method="POST" action="{{ route('stock.move') }}" class="card mw-700px">
        @csrf
        <div class="card-body row">
            <div class="col-md-6"><x-dashboard.field name="type" label="Jenis mutasi" type="select" required :options="['masuk_donasi' => 'Masuk (donasi barang)', 'keluar_pakai' => 'Keluar (pemakaian umum)', 'keluar_rusak' => 'Keluar (rusak/hilang)', 'transfer' => 'Pindah antargudang']" /></div>
            <div class="col-md-6"><x-dashboard.field name="date" label="Tanggal" type="date" required :value="today()->toDateString()" /></div>
            <div class="col-md-6"><x-dashboard.field name="item_id" label="Barang" type="select" required :options="$items->mapWithKeys(fn ($i) => [$i->id => $i->label()])->all()" data-control="select2" /></div>
            <div class="col-md-6"><x-dashboard.field name="qty" label="Jumlah" type="number" step="0.001" min="0.001" required /></div>
            <div class="col-md-6"><x-dashboard.field name="warehouse_id" label="Gudang" type="select" required :options="$warehouses->pluck('name', 'id')->all()" /></div>
            <div class="col-md-6"><x-dashboard.field name="to_warehouse_id" label="Gudang tujuan (pindah)" type="select" :options="$warehouses->pluck('name', 'id')->all()" /></div>
            <div class="col-md-6"><x-dashboard.field name="unit_cost" label="Nilai wajar per satuan (donasi)" type="number" step="1" min="0" /></div>
            <div class="col-md-6"><x-dashboard.field name="note" label="Catatan" help="Wajib untuk barang rusak/hilang." /></div>
        </div>
        <div class="card-footer text-muted fs-7 d-flex flex-wrap gap-3 align-items-center">
            <span class="me-auto">Pembelian dicatat lewat menu Pembelian; pemakaian dapur & kebun lewat form masak/log kebun.</span>
            <a href="{{ route('item.index') }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-primary">Simpan</button>
        </div>
    </form>
</x-dashboard.page>
