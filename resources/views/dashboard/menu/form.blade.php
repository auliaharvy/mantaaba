@php($editing = $menu->exists)
<x-dashboard.page :title="$editing ? 'Ubah Menu' : 'Tambah Menu'" :crumbs="['Website', 'Menu', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('menu.update', $menu) : route('menu.store') }}" class="card">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Menu</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('menu.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body row">
            <div class="col-md-4"><x-dashboard.field name="location" label="Lokasi" type="select" :options="App\Models\NavMenu::LOCATIONS" :value="$menu->location" required /></div>
            <div class="col-md-4" data-show-location="header"><x-dashboard.field name="parent_id" label="Induk" type="select" :options="$parents" :value="$menu->parent_id" placeholder="— Tingkat utama —" /></div>
            <div class="col-md-4"><x-dashboard.field name="label" label="Label" :value="$menu->label" required /></div>
            <div class="col-md-4"><x-dashboard.field name="type" label="Jenis tautan" type="select" :options="App\Models\NavMenu::TYPES" :value="$menu->type" required /></div>
            <div class="col-md-8" data-show-type="page"><x-dashboard.field name="page_id" label="Halaman" type="select" :options="$pages" :value="$menu->page_id" /></div>
            <div class="col-md-8" data-show-type="route"><x-dashboard.field name="route_name" label="Halaman bawaan" type="select" :options="config('mantaaba.menu_routes')" :value="$menu->route_name" /></div>
            <div class="col-md-8" data-show-type="url"><x-dashboard.field name="url" label="URL" :value="$menu->url" placeholder="https:// atau /alamat" /></div>
            <div class="col-12 d-flex flex-wrap gap-8">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $menu->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="open_new_tab" name="open_new_tab" value="1" @checked(old('open_new_tab', $menu->open_new_tab))>
                    <label class="form-check-label" for="open_new_tab">Buka di tab baru</label>
                </div>
            </div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
    </form>
    <x-slot name="javascript">
        <script>
            function toggleMenuFields() {
                const type = document.getElementById('field-type').value, location = document.getElementById('field-location').value;
                document.querySelectorAll('[data-show-type]').forEach((el) => el.classList.toggle('d-none', el.dataset.showType !== type));
                document.querySelectorAll('[data-show-location]').forEach((el) => el.classList.toggle('d-none', el.dataset.showLocation !== location));
                if (location !== 'header') document.getElementById('field-parent_id').value = '';
            }
            document.querySelectorAll('#field-type, #field-location').forEach((el) => el.addEventListener('change', toggleMenuFields));
            toggleMenuFields();
        </script>
    </x-slot>
</x-dashboard.page>
