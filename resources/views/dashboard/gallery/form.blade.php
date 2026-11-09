@php($editing = $gallery->exists)
<x-dashboard.page :title="$editing ? 'Ubah Album' : 'Tambah Album'" :crumbs="['Website', 'Galeri', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('gallery.update', $gallery) : route('gallery.store') }}" enctype="multipart/form-data" class="card mb-5">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Album</div>
            <div class="card-toolbar gap-2">
                @if ($editing)<a class="btn btn-sm btn-light" href="{{ route('site.gallery.show', $gallery->slug) }}" target="_blank"><i class="fa fa-external-link-alt"></i> Lihat</a>@endif
                <a class="btn btn-sm btn-light-primary" href="{{ route('gallery.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
        <div class="card-body row">
            <div class="col-md-8"><x-dashboard.field name="title" label="Judul" :value="$gallery->title" required /></div>
            <div class="col-md-4"><x-dashboard.field name="published_at" label="Tanggal terbit" type="date" :value="$gallery->published_at" help="Kosong = draf" /></div>
            <div class="col-md-6"><x-dashboard.field name="event_id" label="Terkait acara (opsional)" type="select" :options="$events" :value="$gallery->event_id" placeholder="— Tidak terkait acara —" /></div>
            <div class="col-md-6">
                <x-dashboard.field name="cover" label="Sampul (opsional, default foto pertama)" type="file" accept="image/*" />
            </div>
            <div class="col-12"><x-dashboard.field name="description" label="Deskripsi" type="textarea" :value="$gallery->description" /></div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan album</button></div>
    </form>

    @if ($editing)
        <div class="card">
            <div class="card-header"><div class="card-title fs-3 fw-bolder">Foto ({{ $gallery->photos->count() }})</div></div>
            <div class="card-body">
                <form method="POST" action="{{ route('gallery.photos.store', $gallery) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-3 align-items-end mb-8">
                    @csrf
                    <div class="flex-grow-1">
                        <label class="form-label" for="photos">Unggah foto (boleh banyak sekaligus, maks 30 × 3 MB)</label>
                        <input class="form-control @error('photos.*') is-invalid @enderror" id="photos" type="file" name="photos[]" accept="image/*" multiple required>
                        @error('photos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-upload"></i> Unggah</button>
                </form>

                @if ($gallery->photos->isNotEmpty())
                    <form method="POST" action="{{ route('gallery.photos.update', $gallery) }}" id="photo-meta">
                        @csrf @method('PATCH')
                    </form>
                    <div class="row g-4">
                        @foreach ($gallery->photos as $photo)
                            <div class="col-6 col-md-4 col-xl-3">
                                <div class="border rounded p-2 h-100">
                                    <img src="{{ Storage::disk('public')->url($photo->image) }}" alt="" class="w-100 rounded mb-2" style="aspect-ratio: 4/3; object-fit: cover" loading="lazy">
                                    <input form="photo-meta" class="form-control form-control-sm mb-2" name="photos[{{ $photo->id }}][caption]" value="{{ $photo->caption }}" placeholder="Keterangan" aria-label="Keterangan foto">
                                    <div class="d-flex gap-2">
                                        <input form="photo-meta" class="form-control form-control-sm" type="number" min="0" name="photos[{{ $photo->id }}][sort_order]" value="{{ $photo->sort_order }}" aria-label="Urutan foto">
                                        <form method="POST" action="{{ route('gallery.photos.destroy', $photo) }}" onsubmit="return confirm('Hapus foto ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-icon btn-light-danger" title="Hapus foto"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-end mt-5"><button form="photo-meta" class="btn btn-sm btn-primary" type="submit">Simpan keterangan & urutan</button></div>
                @endif
            </div>
        </div>
    @endif
</x-dashboard.page>
