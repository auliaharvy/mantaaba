@php
    $editing = $asset->exists;
    $locked = $editing && ($asset->journal_id || $asset->disposed_at);
    $v = $vehicle;
@endphp
<x-dashboard.page :title="$editing ? 'Ubah Aset' : 'Tambah Aset'" :crumbs="['Operasional', 'Aset', $editing ? $asset->code : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('asset.update', $asset) : route('asset.store') }}" enctype="multipart/form-data" class="card">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card-body row">
            <div class="col-md-6"><x-dashboard.field name="name" label="Nama aset" required :value="$asset->name" placeholder="Toyota Avanza" /></div>
            <div class="col-md-3"><x-dashboard.field name="category" label="Kategori" type="select" required :value="$asset->category" :options="collect(config('mantaaba.asset_categories'))->map(fn ($c) => $c[0])->all()" /></div>
            <div class="col-md-3"><x-dashboard.field name="condition" label="Kondisi" type="select" required :value="$asset->condition" :options="collect(App\Models\Asset::CONDITIONS)->except('dilepas')->all()" /></div>
            <div class="col-md-4"><x-dashboard.field name="location" label="Lokasi" :value="$asset->location" /></div>
            <div class="col-md-4"><x-dashboard.field name="pic_user_id" label="PIC" type="select" :value="$asset->pic_user_id" :options="$users->pluck('name', 'id')->all()" /></div>
            <div class="col-md-4"><x-dashboard.field name="source" label="Sumber" type="select" required :value="$asset->source" :options="App\Models\Asset::SOURCES" /></div>
            <div class="col-md-4"><x-dashboard.field name="acquired_at" label="Tanggal perolehan" type="date" required :value="$asset->acquired_at" :readonly="$locked" /></div>
            <div class="col-md-4"><x-dashboard.field name="acquisition_value" label="Nilai perolehan (Rp)" type="number" step="1" min="0" required :value="$asset->acquisition_value" :readonly="$locked" /></div>
            <div class="col-md-4"><x-dashboard.field name="residual_value" label="Nilai residu (Rp)" type="number" step="1" min="0" :value="$asset->residual_value" /></div>
            <div class="col-md-4"><x-dashboard.field name="useful_life_months" label="Umur manfaat (bulan)" type="number" min="0" :value="$asset->useful_life_months" help="Kosong = bawaan kategori; tanah tidak disusutkan." /></div>
            <div class="col-md-4"><x-dashboard.field name="depreciation_start" label="Mulai penyusutan" type="date" :value="$asset->depreciation_start" help="Kosong = tanggal perolehan." /></div>
            <div class="col-md-4"><x-dashboard.field name="opening_accumulated" label="Akumulasi penyusutan s.d. mulai pembukuan" type="number" step="1" min="0" :value="$asset->opening_accumulated" help="Hanya untuk aset lama." /></div>
            @unless ($editing)
                <div class="col-md-6"><x-dashboard.field name="recording" label="Pencatatan perolehan" type="select" required value="tunai" :options="collect(App\Services\AssetService::RECORDINGS)->map(fn ($r) => $r[0] . ($r[1] ? ' (' . $r[1] . ')' : ''))->all()" help="Membuat voucher perolehan untuk disetujui Bendahara, kecuali aset lama." /></div>
                <div class="col-md-6"><x-dashboard.field name="party_id" label="Pemasok/pemberi" type="select" :options="$parties->pluck('name', 'id')->all()" help="Wajib untuk beli kredit." /></div>
            @endunless
            <div class="col-md-6"><x-dashboard.field name="photo" label="Foto" type="file" accept="image/*" /></div>
            <div class="col-md-6"><x-dashboard.field name="documents[]" id="field-documents" label="Dokumen (BPKB, STNK, nota; privat)" type="file" accept="image/*,application/pdf" multiple /></div>
            <div class="col-12"><x-dashboard.field name="notes" label="Catatan" type="textarea" :value="$asset->notes" /></div>
        </div>
        <div class="card-body border-top row" id="vehicle-fields">
            <h4 class="fs-6 fw-bolder mb-4">Data kendaraan (khusus kategori Kendaraan)</h4>
            <div class="col-md-3"><x-dashboard.field name="vehicle[plate_number]" id="v-plate" label="Nomor polisi" :value="$v?->plate_number" /></div>
            <div class="col-md-3"><x-dashboard.field name="vehicle[brand_type]" id="v-brand" label="Merek/tipe" :value="$v?->brand_type" /></div>
            <div class="col-md-3"><x-dashboard.field name="vehicle[year]" id="v-year" label="Tahun" type="number" :value="$v?->year" /></div>
            <div class="col-md-3"><x-dashboard.field name="vehicle[color]" id="v-color" label="Warna" :value="$v?->color" /></div>
            <div class="col-md-4"><x-dashboard.field name="vehicle[tax_due_at]" id="v-tax" label="Jatuh tempo pajak" type="date" :value="$v?->tax_due_at" /></div>
            <div class="col-md-4"><x-dashboard.field name="vehicle[stnk_due_at]" id="v-stnk" label="Jatuh tempo STNK" type="date" :value="$v?->stnk_due_at" /></div>
            <div class="col-md-4"><x-dashboard.field name="vehicle[kir_due_at]" id="v-kir" label="Jatuh tempo KIR" type="date" :value="$v?->kir_due_at" /></div>
            <div class="col-md-3"><x-dashboard.field name="vehicle[last_odometer]" id="v-odo" label="Odometer (km)" type="number" min="0" :value="$v?->last_odometer" /></div>
            <div class="col-md-3"><x-dashboard.field name="vehicle[service_interval_km]" id="v-int-km" label="Interval servis (km)" type="number" min="0" :value="$v?->service_interval_km" /></div>
            <div class="col-md-3"><x-dashboard.field name="vehicle[service_interval_days]" id="v-int-day" label="Interval servis (hari)" type="number" min="0" :value="$v?->service_interval_days" /></div>
            <div class="col-md-3"><x-dashboard.field name="vehicle[last_service_km]" id="v-svc-km" label="Servis terakhir (km)" type="number" min="0" :value="$v?->last_service_km" /></div>
            <div class="col-md-3"><x-dashboard.field name="vehicle[last_service_at]" id="v-svc-at" label="Servis terakhir (tanggal)" type="date" :value="$v?->last_service_at" /></div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ $editing ? route('asset.show', $asset) : route('asset.index') }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-primary">Simpan</button>
        </div>
    </form>
    <x-slot name="javascript"><script>
        (function () {
            const category = document.getElementById('field-category');
            const box = document.getElementById('vehicle-fields');
            const sync = () => { box.style.display = category.value === 'kendaraan' ? '' : 'none'; };
            category.addEventListener('change', sync);
            sync();
        })();
    </script></x-slot>
</x-dashboard.page>
