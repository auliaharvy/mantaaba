@php($editing = $batch->exists)
<x-dashboard.page :title="$editing ? 'Ubah Angkatan' : 'Tambah Angkatan'" :crumbs="['Penerimaan', 'Angkatan', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('batch.update', $batch) : route('batch.store') }}" class="card">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-header">
            <div class="card-title fs-3 fw-bolder">Angkatan</div>
            <div class="card-toolbar"><a class="btn btn-sm btn-light-primary" href="{{ route('batch.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a></div>
        </div>
        <div class="card-body row">
            <div class="col-md-3"><x-dashboard.field name="code" label="Kode" :value="$batch->code" required placeholder="2027-1" /></div>
            <div class="col-md-6"><x-dashboard.field name="name" label="Nama" :value="$batch->name" required placeholder="Angkatan 1 Tahun 2027" /></div>
            <div class="col-md-3"><x-dashboard.field name="quota" label="Kuota siswa" type="number" min="1" :value="$batch->quota" required /></div>
            <div class="col-md-3"><x-dashboard.field name="admission_opens_at" label="Pendaftaran dibuka" type="date" :value="$batch->admission_opens_at" /></div>
            <div class="col-md-3"><x-dashboard.field name="admission_closes_at" label="Pendaftaran ditutup" type="date" :value="$batch->admission_closes_at" /></div>
            <div class="col-md-3"><x-dashboard.field name="starts_at" label="Mulai inkubasi" type="date" :value="$batch->starts_at" /></div>
            <div class="col-md-3"><x-dashboard.field name="ends_at" label="Selesai inkubasi" type="date" :value="$batch->ends_at" /></div>
            <div class="col-md-4"><x-dashboard.field name="status" label="Status" type="select" :options="App\Models\Batch::STATUSES" :value="$batch->status" required help="Formulir publik tampil bila status 'Pendaftaran dibuka' dan hari ini di dalam jendela pendaftaran." /></div>
            <div class="col-md-8"><x-dashboard.field name="requirements_note" label="Catatan persyaratan khusus angkatan" type="textarea" :value="$batch->requirements_note" /></div>
            <div class="col-12"><x-dashboard.field name="description" label="Keterangan" type="textarea" :value="$batch->description" /></div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
    </form>
</x-dashboard.page>
