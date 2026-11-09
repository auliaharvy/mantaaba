<x-dashboard.page :title="'Ubah: ' . $page->title" :crumbs="['Website', 'Halaman', 'Ubah']">
    <form method="POST" action="{{ route('page.update', $page) }}" enctype="multipart/form-data">
        @csrf @method('PATCH')
        <div class="card">
            <div class="card-header">
                <div class="card-title fs-3 fw-bolder">{{ $page->title }} <span class="text-muted fs-6 ms-3">/{{ $page->slug }}</span></div>
                <div class="card-toolbar gap-2">
                    <a class="btn btn-sm btn-light" href="{{ route('site.page', $page->slug) }}" target="_blank"><i class="fa fa-external-link-alt"></i> Lihat</a>
                    <a class="btn btn-sm btn-light-primary" href="{{ route('page.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
                </div>
            </div>
            <div class="card-body">
                <x-dashboard.field name="title" label="Judul" :value="$page->title" required />
                <x-dashboard.field name="excerpt" label="Ringkasan" type="textarea" :value="$page->excerpt" />
                <div class="mb-6">
                    <label class="form-label" for="body">Isi</label>
                    <textarea id="body" name="body">{{ old('body', $page->body) }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-dashboard.field name="cover" label="Gambar sampul (jpg/png/webp, maks 3 MB)" type="file" accept="image/*" />
                        @if ($page->cover)<img src="{{ Storage::disk('public')->url($page->cover) }}" alt="" class="h-75px rounded mb-6">@endif
                    </div>
                    <div class="col-md-6">
                        <x-dashboard.field name="meta_title" label="Judul SEO" :value="$page->meta_title" help="Kosongkan untuk memakai judul halaman." />
                        <x-dashboard.field name="meta_description" label="Deskripsi SEO" type="textarea" :value="$page->meta_description" />
                    </div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published', $page->is_published))>
                    <label class="form-check-label" for="is_published">Terbit</label>
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
        </div>
    </form>
    <x-slot name="javascript">@include('dashboard.partials.tinymce')</x-slot>
</x-dashboard.page>
