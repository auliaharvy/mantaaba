@php($editing = $unit->exists)
<x-dashboard.page :title="$editing ? 'Ubah Unit Bisnis' : 'Tambah Unit Bisnis'" :crumbs="['Website', 'Unit Bisnis', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('business-unit.update', $unit) : route('business-unit.store') }}" enctype="multipart/form-data">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="row g-5">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8"><x-dashboard.field name="name" label="Nama" :value="$unit->name" required /></div>
                            <div class="col-md-4"><x-dashboard.field name="type" label="Jenis" type="select" :options="App\Models\BusinessUnit::TYPES" :value="$unit->type" required /></div>
                        </div>
                        <x-dashboard.field name="field" label="Bidang usaha / kegiatan" :value="$unit->field" placeholder="Pertanian, perikanan, katering, ..." />
                        <x-dashboard.field name="summary" label="Ringkasan (kartu di situs)" type="textarea" :value="$unit->summary" />
                        <div class="mb-6">
                            <label class="form-label" for="body">Profil lengkap</label>
                            <textarea id="body" name="body">{{ old('body', $unit->body) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-5">
                    <div class="card-body">
                        <x-dashboard.field name="logo" label="Logo" type="file" accept="image/*" />
                        @if ($unit->logo)<img src="{{ Storage::disk('public')->url($unit->logo) }}" alt="" class="h-50px rounded mb-6">@endif
                        <x-dashboard.field name="cover" label="Foto sampul" type="file" accept="image/*" />
                        @if ($unit->cover)<img src="{{ Storage::disk('public')->url($unit->cover) }}" alt="" class="w-100 rounded mb-6">@endif
                        <x-dashboard.field name="gallery_id" label="Album galeri" type="select" :options="$galleries" :value="$unit->gallery_id" placeholder="— Tanpa album —" />
                        <x-dashboard.field name="sort_order" label="Urutan" type="number" min="0" :value="$unit->sort_order ?? 0" />
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" @checked(old('is_public', $unit->is_public ?? true))>
                            <label class="form-check-label" for="is_public">Tampil di situs (/ekosistem)</label>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><div class="card-title fs-5 fw-bolder">Kontak</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="contact_name" label="Penanggung jawab" :value="$unit->contact_name" />
                        <x-dashboard.field name="phone" label="Telepon / WA" :value="$unit->phone" />
                        <x-dashboard.field name="website" label="Website" type="url" :value="$unit->website" placeholder="https://" />
                        <x-dashboard.field name="address" label="Alamat" :value="$unit->address" />
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
                </div>
            </div>
        </div>
    </form>
    <x-slot name="javascript">@include('dashboard.partials.tinymce')</x-slot>
</x-dashboard.page>
