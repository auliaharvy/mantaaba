@php($editing = $document->exists)
<x-dashboard.page :title="$editing ? 'Ubah Dokumen' : 'Tambah Dokumen'" :crumbs="['Website', 'Dokumen', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('document.update', $document) : route('document.store') }}" enctype="multipart/form-data" class="card">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Dokumen</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('document.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body row">
            <div class="col-md-8"><x-dashboard.field name="title" label="Judul" :value="$document->title" required /></div>
            <div class="col-md-4"><x-dashboard.field name="category" label="Kategori" type="select" :options="array_combine(App\Models\Document::CATEGORIES, App\Models\Document::CATEGORIES)" :value="$document->category" required /></div>
            <div class="col-md-6">
                <x-dashboard.field name="file" label="File PDF (maks 10 MB)" type="file" accept="application/pdf" :required="! $editing" />
                @if ($document->file)<a href="{{ Storage::disk('public')->url($document->file) }}" target="_blank" class="fs-7">Lihat file saat ini</a>@endif
            </div>
            <div class="col-md-3"><x-dashboard.field name="published_at" label="Tanggal terbit" type="date" :value="$document->published_at" help="Kosong = draf" /></div>
            <div class="col-md-3"><x-dashboard.field name="sort_order" label="Urutan" type="number" min="0" :value="$document->sort_order ?? 0" /></div>
            <div class="col-12"><x-dashboard.field name="description" label="Keterangan" type="textarea" :value="$document->description" /></div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
    </form>
</x-dashboard.page>
