@php($editing = $party->exists)
<x-dashboard.page :title="$editing ? 'Ubah Pihak ' . $party->code : 'Tambah Pihak'" :crumbs="['Keuangan', 'Pihak', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('party.update', $party) : route('party.store') }}" class="card mw-800px">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-body row">
            <div class="col-md-8"><x-dashboard.field name="name" label="Nama" :value="$party->name" required /></div>
            <div class="col-md-4"><x-dashboard.field name="type" label="Jenis" type="select" :options="App\Models\Party::TYPES" :value="$party->type" required /></div>
            <div class="col-md-4"><x-dashboard.field name="phone" label="Telepon" :value="$party->phone" /></div>
            <div class="col-md-4"><x-dashboard.field name="email" label="Email" type="email" :value="$party->email" /></div>
            <div class="col-md-4"><x-dashboard.field name="npwp" label="NPWP" :value="$party->npwp" /></div>
            <div class="col-md-8"><x-dashboard.field name="address" label="Alamat" :value="$party->address" /></div>
            <div class="col-md-4"><x-dashboard.field name="member_id" label="Terkait anggota" type="select" :options="$members" :value="$party->member_id" placeholder="—" /></div>
            <div class="col-12"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $party->is_active ?? true))> <span class="form-check-label">Aktif</span></label></div>
        </div>
        <div class="card-footer text-end"><a href="{{ route('party.index') }}" class="btn btn-sm btn-light">Batal</a> <button class="btn btn-sm btn-primary">Simpan</button></div>
    </form>
</x-dashboard.page>
