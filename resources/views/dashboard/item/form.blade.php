@php
    $editing = $item->exists;
    $label = fn ($a) => $a->label();
@endphp
<x-dashboard.page :title="$editing ? 'Ubah Barang' : 'Tambah Barang'" :crumbs="['Operasional', 'Barang', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('item.update', $item) : route('item.store') }}" class="card mw-800px">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card-body row">
            <div class="col-md-6"><x-dashboard.field name="name" label="Nama barang" :value="$item->name" required placeholder="Beras" /></div>
            <div class="col-md-3"><x-dashboard.field name="unit" label="Satuan" :value="$item->unit" required placeholder="kg" /></div>
            <div class="col-md-3"><x-dashboard.field name="min_stock" label="Stok minimum" type="number" step="0.001" min="0" :value="$item->min_stock" /></div>
            <div class="col-md-6"><x-dashboard.field name="category" label="Kategori" type="select" required :value="$item->category" :options="collect(config('mantaaba.item_categories'))->map(fn ($c) => $c[0])->all()" help="Akun persediaan & beban mengikuti kategori bila tidak dipilih." /></div>
            <div class="col-md-6 d-flex align-items-center"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> <span class="form-check-label">Aktif</span></label></div>
            <div class="col-md-6"><x-dashboard.field name="inventory_account_id" label="Akun persediaan" type="select" placeholder="— Sesuai kategori —" :value="$item->inventory_account_id" :options="($accounts['aset'] ?? collect())->mapWithKeys(fn ($a) => [$a->id => $a->label()])->all()" /></div>
            <div class="col-md-6"><x-dashboard.field name="expense_account_id" label="Akun beban pemakaian" type="select" placeholder="— Sesuai kategori —" :value="$item->expense_account_id" :options="($accounts['beban'] ?? collect())->mapWithKeys(fn ($a) => [$a->id => $a->label()])->all()" /></div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ $editing ? route('item.show', $item) : route('item.index') }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-primary">Simpan</button>
        </div>
    </form>
</x-dashboard.page>
