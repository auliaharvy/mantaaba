<x-dashboard.page title="Tambah Halaman" :crumbs="['Website', 'Halaman', 'Tambah']">
    <form method="POST" action="{{ route('page.store') }}" class="card mw-700px">
        @csrf
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Halaman baru</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('page.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body">
            <x-dashboard.field name="title" label="Judul" required />
            <x-dashboard.field name="slug" label="Alamat halaman" :placeholder="'dibuat dari judul, mis. kemitraan'" help="Halaman tampil di {{ url('/') }}/alamat-halaman. Tidak bisa diubah setelah dibuat." />
            <x-dashboard.field name="template" label="Jenis isi" type="select" :options="App\Models\Page::TEMPLATES" value="blocks" required />
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Buat halaman</button></div>
    </form>
</x-dashboard.page>
