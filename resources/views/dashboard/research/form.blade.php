@php($editing = $research->exists)
<x-dashboard.page :title="$editing ? 'Ubah Jurnal' : 'Tambah Jurnal'" :crumbs="['Postingan', 'Jurnal', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('research.update', $research) : route('research.store') }}" enctype="multipart/form-data" class="card">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Jurnal</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('research.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body row">
            <div class="col-md-8"><x-dashboard.field name="title" label="Judul" :value="$research->title" required /></div>
            <div class="col-md-4"><x-dashboard.field name="date_of_publish" label="Tanggal terbit" type="date" :value="$research->date_of_publish" required /></div>
            <div class="col-md-8"><x-dashboard.field name="authors" label="Penulis (pisahkan dengan koma)" :value="$research->authors" /></div>
            <div class="col-md-4">
                <x-dashboard.field name="category" label="Kategori" :value="$research->category" list="research-categories" />
                <datalist id="research-categories">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
            </div>
            <div class="col-12"><x-dashboard.field name="abstract" label="Abstrak" type="textarea" :value="$research->abstract" rows="6" /></div>
            <div class="col-md-8"><x-dashboard.field name="keywords" label="Kata kunci (pisahkan dengan koma)" :value="$research->keywords" /></div>
            <div class="col-md-4"><x-dashboard.field name="status" label="Status" type="select" :options="['published' => 'Terbit', 'draft' => 'Draf']" :value="$research->status" required /></div>
            <div class="col-md-6">
                <x-dashboard.field name="file" label="File PDF (maks 10 MB)" type="file" accept="application/pdf" :required="! $editing" />
                @if ($research->file)<span class="fs-7 text-muted">File saat ini: {{ basename($research->file) }}</span>@endif
            </div>
            <div class="col-md-6"><x-dashboard.field name="cover" label="Sampul (opsional)" type="file" accept="image/*" /></div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
    </form>
</x-dashboard.page>
