@php
    $editing = $plot->exists;
@endphp
<x-dashboard.page :title="$editing ? 'Ubah Petak/Kolam' : 'Tambah Petak/Kolam'" :crumbs="['Operasional', 'Kebun & Kolam', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('farm.plot-update', $plot) : route('farm.plot-store') }}" enctype="multipart/form-data" class="card mw-700px">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card-body row">
            <div class="col-md-4"><x-dashboard.field name="type" label="Jenis" type="select" required :value="$plot->type" :options="App\Models\FarmPlot::TYPES" /></div>
            <div class="col-md-3"><x-dashboard.field name="code" label="Kode" required :value="$plot->code" placeholder="L-01" /></div>
            <div class="col-md-5"><x-dashboard.field name="name" label="Nama" required :value="$plot->name" placeholder="Bedengan Timur" /></div>
            <div class="col-md-4"><x-dashboard.field name="size" label="Luas/volume" type="number" step="0.01" min="0" :value="$plot->size" /></div>
            <div class="col-md-3"><x-dashboard.field name="size_unit" label="Satuan" type="select" required :value="$plot->size_unit" :options="['m2' => 'm²', 'm3' => 'm³']" /></div>
            <div class="col-md-5"><x-dashboard.field name="status" label="Status" type="select" :value="$plot->status" :options="App\Models\FarmPlot::STATUSES" /></div>
            <div class="col-md-6"><x-dashboard.field name="pic_member_id" label="PIC" type="select" :value="$plot->pic_member_id" :options="$members->mapWithKeys(fn ($m) => [$m->id => $m->name])->all()" /></div>
            <div class="col-md-6"><x-dashboard.field name="location_note" label="Lokasi" :value="$plot->location_note" /></div>
            <div class="col-md-6"><x-dashboard.field name="photo" label="Foto" type="file" accept="image/*" /></div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('farm.index') }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-primary">Simpan</button>
        </div>
    </form>
</x-dashboard.page>
