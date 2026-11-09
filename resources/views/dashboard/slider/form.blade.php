@php($editing = $slider->exists)
<x-dashboard.page :title="$editing ? 'Ubah Slider' : 'Tambah Slider'" :crumbs="['Website', 'Slider', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('slider.update', $slider) : route('slider.store') }}" enctype="multipart/form-data" class="card">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Slider</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('slider.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body row">
            <div class="col-md-6"><x-dashboard.field name="title" label="Judul" :value="$slider->title" required /></div>
            <div class="col-md-6"><x-dashboard.field name="subtitle" label="Subjudul" :value="$slider->subtitle" /></div>
            <div class="col-md-6">
                <x-dashboard.field name="image" label="Gambar (lebar ≥ 1600px disarankan, maks 3 MB)" type="file" accept="image/*" :required="! $editing" />
                @if ($slider->image)<img src="{{ Storage::disk('public')->url($slider->image) }}" alt="" class="h-75px rounded mb-6">@endif
            </div>
            <div class="col-md-3"><x-dashboard.field name="button_text" label="Teks tombol" :value="$slider->button_text" placeholder="Daftar Sekarang" /></div>
            <div class="col-md-3"><x-dashboard.field name="sort_order" label="Urutan" type="number" min="0" :value="$slider->sort_order ?? 0" /></div>
            <div class="col-md-9"><x-dashboard.field name="button_url" label="Tautan tombol (URL lengkap)" type="url" :value="$slider->button_url" placeholder="https://" /></div>
            <div class="col-md-3 form-check form-switch pt-12 ps-14">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $slider->is_active))>
                <label class="form-check-label" for="is_active">Aktif</label>
            </div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
    </form>
</x-dashboard.page>
