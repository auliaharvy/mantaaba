@php($editing = $skema->exists)
@php($matrix = array_pad(old('matrix', $skema->character_matrix ?? []), max(2, count($skema->character_matrix ?? []) + 1), []))
<x-dashboard.page :title="$editing ? 'Ubah Skema ' . $skema->code : 'Tambah Skema'" :crumbs="['Pembelajaran', 'Skema', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('skema.update', $skema) : route('skema.store') }}" enctype="multipart/form-data">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card mb-5">
            <div class="card-header"><div class="card-title fs-4 fw-bolder">Skema</div></div>
            <div class="card-body row">
                <div class="col-md-2"><x-dashboard.field name="code" label="Kode" :value="$skema->code" required placeholder="SIA" /></div>
                <div class="col-md-5"><x-dashboard.field name="name" label="Nama" :value="$skema->name" required /></div>
                <div class="col-md-2"><x-dashboard.field name="month_label" label="Waktu" :value="$skema->month_label" placeholder="Bulan 4" /></div>
                <div class="col-md-2"><x-dashboard.field name="pass_score" label="Nilai lulus" type="number" min="0" max="100" :value="$skema->pass_score" :placeholder="'Setting: ' . App\Models\Setting::current()->default_pass_score" /></div>
                <div class="col-md-1"><x-dashboard.field name="sort_order" label="Urutan" type="number" min="0" :value="$skema->sort_order" /></div>
                <div class="col-12"><x-dashboard.field name="description" label="Deskripsi singkat" type="textarea" :value="$skema->description" /></div>
                <div class="col-12 mb-6"><label class="form-label" for="body">Tujuan pembelajaran</label><textarea id="body" name="objectives">{{ old('objectives', $skema->objectives) }}</textarea></div>
                <div class="col-12 form-check form-switch ms-3"><input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" @checked(old('is_public', $skema->is_public))><label class="form-check-label" for="is_public">Tampil di halaman Kurikulum</label></div>
            </div>
        </div>
        <div class="card mb-5">
            <div class="card-header"><div class="card-title fs-4 fw-bolder">Matriks kompetensi & karakter <span class="text-muted fs-7 ms-2">(Grand Design 5)</span></div></div>
            <div class="card-body">
                @foreach ($matrix as $i => $row)
                    <div class="row g-3 mb-4 border-bottom pb-4">
                        <div class="col-md-3"><input class="form-control form-control-sm" name="matrix[{{ $i }}][competency]" value="{{ $row['competency'] ?? '' }}" placeholder="Kompetensi (mis. Komunikasi Bisnis)" aria-label="Kompetensi"></div>
                        <div class="col-md-3"><textarea class="form-control form-control-sm" rows="2" name="matrix[{{ $i }}][technical]" placeholder="Fokus teknis SKKNI" aria-label="Fokus teknis">{{ $row['technical'] ?? '' }}</textarea></div>
                        <div class="col-md-3"><textarea class="form-control form-control-sm" rows="2" name="matrix[{{ $i }}][asabiyah]" placeholder="Penerapan 'Asabiyah" aria-label="Asabiyah">{{ $row['asabiyah'] ?? '' }}</textarea></div>
                        <div class="col-md-3"><textarea class="form-control form-control-sm" rows="2" name="matrix[{{ $i }}][fastabiqul]" placeholder="Penerapan Fastabiqul Khairat" aria-label="Fastabiqul Khairat">{{ $row['fastabiqul'] ?? '' }}</textarea></div>
                    </div>
                @endforeach
                <div class="form-text">Baris dengan kompetensi kosong diabaikan. Simpan untuk menambah baris baru.</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-title fs-4 fw-bolder">Sertifikat</div></div>
            <div class="card-body row">
                <div class="col-12">
                    <x-dashboard.field name="certificate_template" label="Latar sertifikat (gambar A4 landscape, opsional)" type="file" accept="image/*" help="Tanpa latar: bingkai cokelat-emas." />
                    @if ($skema->certificate_template)<img src="{{ Storage::disk('public')->url($skema->certificate_template) }}" alt="" class="h-100px rounded mb-6">@endif
                </div>
                @foreach ([0, 1] as $i)
                    @php($s = $skema->certificate_signatories[$i] ?? [])
                    <div class="col-md-6">
                        <div class="fw-bolder mb-3">Penandatangan {{ $i + 1 }}</div>
                        <x-dashboard.field :name="'signatories[' . $i . '][name]'" :id="'sig-name-' . $i" label="Nama" :value="$s['name'] ?? null" />
                        <x-dashboard.field :name="'signatories[' . $i . '][title]'" :id="'sig-title-' . $i" label="Jabatan" :value="$s['title'] ?? null" placeholder="Ketua Yayasan Mantaaba" />
                        <x-dashboard.field :name="'signatures[' . $i . ']'" :id="'sig-file-' . $i" label="Tanda tangan (PNG transparan)" type="file" accept="image/png,image/webp" />
                        @if (! empty($s['signature']))<img src="{{ Storage::disk('public')->url($s['signature']) }}" alt="" class="h-50px mb-6">@endif
                    </div>
                @endforeach
                <div class="col-12 form-text">Tanpa penandatangan: memakai Ketua & Bendahara dari Setting Situs.</div>
            </div>
            <div class="card-footer text-end"><a href="{{ $editing ? route('skema.show', $skema) : route('skema.index') }}" class="btn btn-sm btn-light">Batal</a> <button class="btn btn-sm btn-primary">Simpan</button></div>
        </div>
    </form>
    <x-slot name="javascript">@include('dashboard.partials.tinymce', ['height' => 300])</x-slot>
</x-dashboard.page>
