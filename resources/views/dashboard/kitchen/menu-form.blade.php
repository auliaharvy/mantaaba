@php
    $editing = $menu->exists;
    $rows = old('ingredients', $menu->ingredients ?? []);
    $rows = array_pad($rows, max(count($rows) + 2, 6), []);
@endphp
<x-dashboard.page :title="$editing ? 'Ubah Menu' : 'Tambah Menu'" :crumbs="['Operasional', 'Dapur', 'Menu']">
    <form method="POST" action="{{ $editing ? route('kitchen.menu-update', $menu) : route('kitchen.menu-store') }}" class="card mw-800px">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card-body row">
            <div class="col-md-6"><x-dashboard.field name="name" label="Nama menu" required :value="$menu->name" placeholder="Lele goreng" /></div>
            <div class="col-md-4"><x-dashboard.field name="category" label="Kategori" type="select" required :value="$menu->category" :options="App\Models\KitchenMenu::CATEGORIES" /></div>
            <div class="col-md-2 d-flex align-items-center"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $menu->is_active))> <span class="form-check-label">Aktif</span></label></div>
            <div class="col-12">
                <label class="form-label">Bahan per porsi</label>
                @foreach ($rows as $i => $row)
                    <div class="d-flex gap-2 mb-2">
                        <select name="ingredients[{{ $i }}][item_id]" class="form-select form-select-sm" aria-label="Bahan {{ $i + 1 }}"><option value="">—</option>@foreach ($items as $it)<option value="{{ $it->id }}" @selected((int) ($row['item_id'] ?? 0) === $it->id)>{{ $it->label() }}</option>@endforeach</select>
                        <input type="number" step="0.001" min="0" name="ingredients[{{ $i }}][qty]" value="{{ $row['qty'] ?? '' }}" class="form-control form-control-sm w-125px" placeholder="per porsi" aria-label="Jumlah bahan {{ $i + 1 }}">
                    </div>
                @endforeach
                <div class="form-text">Contoh: beras 0,1 kg per porsi. Simpan untuk menambah baris kosong.</div>
            </div>
            <div class="col-12 mt-4"><x-dashboard.field name="notes" label="Catatan" type="textarea" :value="$menu->notes" /></div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('kitchen.menus') }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-primary">Simpan</button>
        </div>
    </form>
</x-dashboard.page>
