@php($editing = $event->exists)
@php($dt = fn ($v) => $v ? $v->format('Y-m-d\TH:i') : null)
<x-dashboard.page :title="$editing ? 'Ubah Agenda' : 'Tambah Agenda'" :crumbs="['Kegiatan', 'Agenda', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('event.update', $event) : route('event.store') }}" enctype="multipart/form-data">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="row g-5">
            <div class="col-xl-8">
                <div class="card mb-5">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Informasi Agenda</div></div>
                    <div class="card-body row">
                        <div class="col-12"><x-dashboard.field name="title" label="Judul" :value="$event->title" required /></div>
                        <div class="col-md-4"><x-dashboard.field name="type" label="Jenis" type="select" :options="App\Models\Event::TYPES" :value="$event->type" required /></div>
                        <div class="col-md-4"><x-dashboard.field name="skema_id" label="Skema (untuk jenis Kelas)" type="select" :options="$skemas" :value="$event->skema_id" placeholder="—" help="Kehadiran kelas masuk ringkasan penilaian skema ini." /></div>
                        <div class="col-md-4"><x-dashboard.field name="mode" label="Mode" type="select" :options="App\Models\Event::MODES" :value="$event->mode" required /></div>
                        <div class="col-md-6"><x-dashboard.field name="location" label="Lokasi" :value="$event->location" placeholder="Tajug Mantaaba" /></div>
                        <div class="col-md-6"><x-dashboard.field name="meeting_url" label="Tautan online (tampil ke peserta)" type="url" :value="$event->meeting_url" placeholder="https://" /></div>
                        <div class="col-md-6"><x-dashboard.field name="starts_at" label="Mulai" type="datetime-local" :value="$dt($event->starts_at)" required /></div>
                        <div class="col-md-6"><x-dashboard.field name="ends_at" label="Selesai" type="datetime-local" :value="$dt($event->ends_at)" required /></div>
                        <div class="col-12 mb-6">
                            <label class="form-label" for="body">Deskripsi</label>
                            <textarea id="body" name="body">{{ old('body', $event->body) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-5">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Peserta</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="status" label="Status" type="select" :options="App\Models\Event::STATUSES" :value="$event->status" required help="Draf tidak tampil di situs dan belum menambahkan peserta otomatis." />
                        <x-dashboard.field name="audience" label="Sasaran" type="select" :options="App\Models\Event::AUDIENCES" :value="$event->audience" required help="Umum: daftar lewat situs (tanpa akun). Anggota/angkatan: peserta otomatis." />
                        <x-dashboard.field name="batch_id" label="Angkatan (sasaran satu angkatan)" type="select" :options="$batches" :value="$event->batch_id" placeholder="—" />
                        <x-dashboard.field name="registration_opens_at" label="Pendaftaran dibuka" type="datetime-local" :value="$dt($event->registration_opens_at)" />
                        <x-dashboard.field name="registration_closes_at" label="Pendaftaran ditutup" type="datetime-local" :value="$dt($event->registration_closes_at)" />
                        <x-dashboard.field name="quota" label="Kuota (kosong = tak terbatas)" type="number" min="1" :value="$event->quota" />
                        <x-dashboard.field name="thumbnail" label="Poster (maks 3 MB)" type="file" accept="image/*" />
                        @if ($event->thumbnail)<img src="{{ Storage::disk('public')->url($event->thumbnail) }}" alt="" class="w-100 rounded">@endif
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
                </div>
            </div>
        </div>
    </form>
    <x-slot name="javascript">@include('dashboard.partials.tinymce', ['height' => 360])</x-slot>
</x-dashboard.page>
