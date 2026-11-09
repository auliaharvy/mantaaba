@php($editing = $organization->exists)
<x-dashboard.page :title="$editing ? 'Ubah PC' : 'Tambah PC'" :crumbs="['Organisasi', 'Pengurus Cabang', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('organization.update', $organization) : route('organization.store') }}" autocomplete="off">
        @csrf
        @if ($editing) @method('PATCH') @endif
        <div class="card">
            <div class="card-header">
                <div class="card-title fs-3 fw-bolder">{{ $editing ? $organization->name : 'Pengurus Cabang Baru' }}</div>
                <div class="card-toolbar">
                    <a class="btn btn-sm btn-light-primary" href="{{ route('organization.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8"><x-dashboard.field name="name" label="Nama" :value="$organization->name" required placeholder="PC AKP2I Kota Bandung" /></div>
                    <div class="col-md-4"><x-dashboard.field name="code" label="Kode" :value="$organization->code" placeholder="JABAR-BDG" help="Dipakai di import CSV anggota (kolom kode_pc)." /></div>
                    <div class="col-md-6"><x-dashboard.field name="city" label="Kota/Kabupaten" :value="$organization->city" /></div>
                    <div class="col-md-3"><x-dashboard.field name="phone" label="Telepon" :value="$organization->phone" /></div>
                    <div class="col-md-3"><x-dashboard.field name="email" label="Email" type="email" :value="$organization->email" /></div>
                    <div class="col-12"><x-dashboard.field name="address" label="Alamat" type="textarea" :value="$organization->address" /></div>
                    <div class="col-md-6"><x-dashboard.field name="profile" label="Profil cabang (tampil di halaman publik PC)" type="textarea" rows="6" :value="$organization->profile" /></div>
                    <div class="col-md-6"><x-dashboard.field name="greeting" label="Sambutan Ketua PC" type="textarea" rows="6" :value="$organization->greeting" /></div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $organization->is_active))>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
            </div>
        </div>
    </form>
</x-dashboard.page>
