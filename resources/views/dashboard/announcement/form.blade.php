@php($editing = $announcement->exists)
<x-dashboard.page :title="$editing ? 'Ubah Pengumuman' : 'Tambah Pengumuman'" :crumbs="['Anggota', 'Pengumuman', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('announcement.update', $announcement) : route('announcement.store') }}" class="card mw-900px">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-body row">
            <div class="col-12"><x-dashboard.field name="title" label="Judul" :value="$announcement->title" required /></div>
            <div class="col-12"><x-dashboard.field name="body" label="Isi" type="textarea" :value="$announcement->body" required rows="6" /></div>
            <div class="col-md-4"><x-dashboard.field name="audience" label="Sasaran" type="select" :options="App\Models\Announcement::AUDIENCES" :value="$announcement->audience" required /></div>
            <div class="col-md-4"><x-dashboard.field name="batch_id" label="Angkatan (sasaran satu angkatan)" type="select" :options="$batches" :value="$announcement->batch_id" placeholder="—" /></div>
            <div class="col-md-4"><x-dashboard.field name="published_at" label="Terbit" type="datetime-local" :value="$announcement->published_at" help="Kosong = draf." /></div>
            <div class="col-md-4"><x-dashboard.field name="expires_at" label="Berakhir" type="datetime-local" :value="$announcement->expires_at" /></div>
            <div class="col-md-8 d-flex gap-8 align-items-center">
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned))><label class="form-check-label" for="is_pinned">Sematkan di atas</label></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="send_email" name="send_email" value="1"><label class="form-check-label" for="send_email">Kirim juga ke email sasaran</label></div>
            </div>
        </div>
        <div class="card-footer text-end"><a href="{{ route('announcement.index') }}" class="btn btn-sm btn-light">Batal</a> <button class="btn btn-sm btn-primary">Simpan</button></div>
    </form>
</x-dashboard.page>
