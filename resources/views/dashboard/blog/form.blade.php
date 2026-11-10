@php($editing = $blog->exists)
@php($publisher = auth()->user()->can('publish', App\Models\Blog::class))
<x-dashboard.page :title="$editing ? 'Ubah Tulisan' : 'Tulis Berita/Artikel'" :crumbs="['Postingan', 'Berita & Artikel', $editing ? 'Ubah' : 'Tulis']">
    <form method="POST" action="{{ $editing ? route('blog.update', $blog) : route('blog.store') }}" enctype="multipart/form-data">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="row g-5">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <x-dashboard.field name="title" label="Judul" :value="$blog->title" required />
                        <x-dashboard.field name="excerpt" label="Ringkasan (tampil di daftar & preview tautan)" type="textarea" :value="$blog->excerpt" />
                        <div class="mb-6">
                            <label class="form-label required" for="body">Isi</label>
                            <textarea id="body" name="body">{{ old('body', $blog->body) }}</textarea>
                            @error('body')<div class="text-danger fs-7">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-5">
                    <div class="card-header"><div class="card-title fs-5 fw-bolder">Publikasi</div></div>
                    <div class="card-body">
                        @if ($editing)
                            <p class="text-muted fs-7">Status: <strong>{{ App\Models\Blog::STATUSES[$blog->status] }}</strong>
                                @if ($blog->status === 'published') · <a href="{{ route('site.news.show', $blog->slug) }}" target="_blank">lihat</a>@endif</p>
                        @endif
                        @if ($publisher)
                            <x-dashboard.field name="status" label="Status" type="select" :options="App\Models\Blog::STATUSES" :value="$blog->status === 'draft' && ! $editing ? 'published' : $blog->status" required />
                            <x-dashboard.field name="published_at" label="Waktu terbit" type="datetime-local" :value="$blog->published_at?->format('Y-m-d\TH:i')" help="Kosong = sekarang. Isi waktu mendatang untuk menjadwalkan." />
                        @else
                            <div class="alert alert-light-primary fs-7">Tulisan disimpan sebagai draf dan diterbitkan oleh pengurus yang berwenang. Setelah terbit, tulisan tidak bisa diubah lagi.</div>
                        @endif
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                        @unless ($publisher)
                            <button class="btn btn-sm btn-primary" type="submit" name="status" value="draft">Simpan draf</button>
                        @else
                            <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
                        @endunless
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <x-dashboard.field name="category_id" label="Kategori" type="select" :options="$categories" :value="$blog->category_id" placeholder="— Tanpa kategori —" />
                        <x-dashboard.field name="tags" label="Tag (pisahkan dengan koma)" :value="$blog->tags?->pluck('name')->implode(', ')" placeholder="PPh 21, e-Faktur" />
                        <x-dashboard.field name="thumbnail" label="Gambar utama (16:10, maks 3 MB)" type="file" accept="image/*" />
                        @if ($blog->thumbnail)<img src="{{ Storage::disk('public')->url($blog->thumbnail) }}" alt="" class="w-100 rounded">@endif
                    </div>
                </div>
            </div>
        </div>
    </form>
    <x-slot name="javascript">@include('dashboard.partials.tinymce')</x-slot>
</x-dashboard.page>
