{{-- Editor halaman (PRD 9.1.3). Tombol blok mengirim seluruh form dengan `_op`; urutan blok = urutan di form (drag). --}}
@php($definitions = App\Support\Blocks::definitions())
<x-dashboard.page :title="'Ubah: ' . $page->title" :crumbs="['Website', 'Halaman', 'Ubah']">
    <form method="POST" action="{{ route('page.update', $page) }}" enctype="multipart/form-data" id="page-form">
        @csrf @method('PATCH')
        <div class="row g-5">
            <div class="col-xl-8">
                @if ($page->template === 'blocks')
                    <input type="hidden" name="blocks_present" value="1">
                    @if ($errors->any())
                        <div class="alert alert-danger fs-7">Ada isian blok yang belum benar. Blok yang bermasalah sudah dibuka di bawah.</div>
                    @endif
                    <div id="blocks" class="blocks-sortable">
                        @forelse ($blocks as $key => $block)
                            @if (isset($definitions[$block['type'] ?? '']))
                                @include('dashboard.page.block', ['key' => $key, 'block' => $block, 'def' => $definitions[$block['type']], 'number' => $loop->iteration])
                            @endif
                        @empty
                            <div class="card mb-5"><div class="card-body text-muted">Belum ada blok. Pilih jenis blok di bawah lalu klik <strong>Tambah blok</strong>.</div></div>
                        @endforelse
                    </div>
                    <div class="card">
                        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                            <select name="new_block" class="form-select form-select-sm w-auto" aria-label="Jenis blok baru">
                                @foreach ($definitions as $type => $def)<option value="{{ $type }}">{{ $def['label'] }}</option>@endforeach
                            </select>
                            <button type="submit" name="_op" value="add" class="btn btn-sm btn-light-primary"><i class="fa fa-plus"></i> Tambah blok</button>
                            <span class="text-muted fs-8">Perubahan lain di halaman ini ikut tersimpan.</span>
                        </div>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body">
                            <label class="form-label" for="body">Isi</label>
                            <textarea id="body" name="body">{{ old('body', $page->body) }}</textarea>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-xl-4">
                <div class="card mb-5">
                    <div class="card-header">
                        <div class="card-title fs-5 fw-bolder">{{ $page->slug === 'home' ? '/' : '/' . $page->slug }}</div>
                        <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('page.index') }}"><i class="fa fa-arrow-left"></i></a></div>
                    </div>
                    <div class="card-body">
                        <x-dashboard.field name="title" label="Judul" :value="$page->title" required />
                        <x-dashboard.field name="template" label="Jenis isi" type="select" :options="App\Models\Page::TEMPLATES" :value="$page->template" required help="Isi jenis lain tetap tersimpan bila diganti." />
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" @checked(old('is_published', $page->is_published)) @disabled($page->is_system)>
                            <label class="form-check-label" for="is_published">Terbit {{ $page->is_system ? '(halaman sistem selalu terbit)' : '' }}</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="show_in_sitemap" name="show_in_sitemap" value="1" @checked(old('show_in_sitemap', $page->show_in_sitemap))>
                            <label class="form-check-label" for="show_in_sitemap">Masuk sitemap.xml</label>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                        <a class="btn btn-sm btn-light" href="{{ $previewUrl }}" target="_blank"><i class="fa fa-eye"></i> Pratinjau</a>
                        @if ($page->is_published)<a class="btn btn-sm btn-light" href="{{ $page->url() }}" target="_blank"><i class="fa fa-external-link-alt"></i> Lihat</a>@endif
                        <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><div class="card-title fs-5 fw-bolder">SEO & pratinjau tautan</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="excerpt" label="Ringkasan" type="textarea" :value="$page->excerpt" />
                        <x-dashboard.field name="meta_title" label="Judul SEO" :value="$page->meta_title" help="Kosongkan untuk memakai judul halaman." />
                        <x-dashboard.field name="meta_description" label="Deskripsi SEO" type="textarea" :value="$page->meta_description" />
                        <x-dashboard.field name="cover" label="Gambar pratinjau tautan (1200×630)" type="file" accept="image/*" />
                        @if ($page->cover)<img src="{{ Storage::disk('public')->url($page->cover) }}" alt="" class="w-100 rounded">@endif
                    </div>
                </div>
            </div>
        </div>
    </form>
    <x-slot name="javascript">
        @include('dashboard.partials.tinymce', ['selector' => $page->template === 'blocks' ? 'textarea.block-rich' : '#body', 'height' => $page->template === 'blocks' ? 320 : 500])
        <script src="{{ asset('plugins/custom/draggable/draggable.bundle.js') }}"></script>
        <script>
            if (document.getElementById('blocks')) {
                // TinyMCE (iframe) rusak bila elemennya dipindah: matikan saat drag, hidupkan lagi sesudahnya
                const sortable = new Sortable.default(document.getElementById('blocks'), { draggable: '.page-block', handle: '.block-handle', mirror: { constrainDimensions: true } });
                sortable.on('sortable:start', () => { tinymce.triggerSave(); tinymce.remove('textarea.block-rich'); });
                sortable.on('sortable:stop', () => setTimeout(() => { tinymce.init(window.tinymceOptions); document.querySelectorAll('#blocks .page-block').forEach((el, i) => el.querySelector('.block-number').textContent = '#' + (i + 1)); }, 0));
            }
            // blok yang baru ditambah/diubah lewat tombol dibuka & digulir ke layar
            const target = location.hash && document.querySelector(location.hash);
            if (target) { target.querySelector('details')?.setAttribute('open', ''); target.scrollIntoView(); }
            document.querySelectorAll('[data-confirm-op]').forEach((btn) => btn.addEventListener('click', (e) => { if (!confirm(btn.dataset.confirmOp)) e.preventDefault(); }));
        </script>
    </x-slot>
</x-dashboard.page>
