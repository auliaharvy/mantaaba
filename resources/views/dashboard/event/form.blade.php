@php($editing = $event->exists)
@php($dt = fn ($v) => $v ? $v->format('Y-m-d\TH:i') : null)
<x-dashboard.page :title="$editing ? 'Ubah Acara' : 'Tambah Acara'" :crumbs="['Kegiatan', 'Acara', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('event.update', $event) : route('event.store') }}" enctype="multipart/form-data">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="row g-5">
            <div class="col-xl-8">
                <div class="card mb-5">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Informasi Acara</div></div>
                    <div class="card-body row">
                        <div class="col-12"><x-dashboard.field name="title" label="Judul" :value="$event->title" required /></div>
                        <div class="col-md-4"><x-dashboard.field name="type" label="Jenis" type="select" :options="App\Models\Event::TYPES" :value="$event->type" required /></div>
                        <div class="col-md-4"><x-dashboard.field name="code" label="Kode (untuk nomor sertifikat)" :value="$event->code" placeholder="Otomatis bila kosong" /></div>
                        <div class="col-md-4"><x-dashboard.field name="skp_hours" label="Jam PPL/SKP" type="number" step="0.5" min="0" :value="$event->skp_hours" /></div>
                        <div class="col-md-4"><x-dashboard.field name="mode" label="Mode" type="select" :options="App\Models\Event::MODES" :value="$event->mode" required /></div>
                        <div class="col-md-8"><x-dashboard.field name="location" label="Lokasi" :value="$event->location" /></div>
                        <div class="col-12"><x-dashboard.field name="meeting_url" label="Tautan acara online (hanya tampil ke peserta terverifikasi)" type="url" :value="$event->meeting_url" placeholder="https://" /></div>
                        <div class="col-md-6"><x-dashboard.field name="starts_at" label="Mulai" type="datetime-local" :value="$dt($event->starts_at)" required /></div>
                        <div class="col-md-6"><x-dashboard.field name="ends_at" label="Selesai" type="datetime-local" :value="$dt($event->ends_at)" required /></div>
                        <div class="col-12 mb-6">
                            <label class="form-label required" for="body">Deskripsi</label>
                            <textarea id="body" name="body">{{ old('body', $event->body) }}</textarea>
                            @error('body')<div class="text-danger fs-7">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Sertifikat</div></div>
                    <div class="card-body row">
                        <div class="col-12">
                            <x-dashboard.field name="certificate_template" label="Latar sertifikat (gambar A4 landscape, opsional)" type="file" accept="image/*" help="Tanpa latar: bingkai hijau sederhana." />
                            @if ($event->certificate_template)<img src="{{ Storage::disk('public')->url($event->certificate_template) }}" alt="" class="h-100px rounded mb-6">@endif
                        </div>
                        @foreach ([0, 1] as $i)
                            @php($s = $event->certificate_signatory[$i] ?? [])
                            <div class="col-md-6">
                                <div class="fw-bolder mb-3">Penandatangan {{ $i + 1 }}</div>
                                <x-dashboard.field name="signatories[{{ $i }}][name]" label="Nama" :value="$s['name'] ?? null" />
                                <x-dashboard.field name="signatories[{{ $i }}][title]" label="Jabatan" :value="$s['title'] ?? null" placeholder="Ketua PD AKP2I Jawa Barat" />
                                <x-dashboard.field name="signatures[{{ $i }}]" label="Tanda tangan (PNG transparan)" type="file" accept="image/png,image/webp" />
                                @if (! empty($s['signature']))<img src="{{ Storage::disk('public')->url($s['signature']) }}" alt="" class="h-50px mb-6">@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-5">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Pendaftaran</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="organization_id" label="Penyelenggara" type="select" :options="$organizations" :value="$event->organization_id ?? $organizations->keys()->first()" required />
                        <x-dashboard.field name="status" label="Status" type="select" :options="App\Models\Event::STATUSES" :value="$event->status" required help="Draf tidak tampil di situs. Sertifikat bisa diterbitkan saat Selesai." />
                        <x-dashboard.field name="audience" label="Peserta" type="select" :options="App\Models\Event::AUDIENCES" :value="$event->audience" required />
                        <x-dashboard.field name="registration_opens_at" label="Pendaftaran dibuka" type="datetime-local" :value="$dt($event->registration_opens_at)" />
                        <x-dashboard.field name="registration_closes_at" label="Pendaftaran ditutup" type="datetime-local" :value="$dt($event->registration_closes_at)" />
                        <x-dashboard.field name="quota" label="Kuota (kosong = tak terbatas)" type="number" min="1" :value="$event->quota" />
                        <x-dashboard.field name="fee_member" label="Biaya anggota aktif (Rp)" type="number" min="0" :value="(int) $event->fee_member" required />
                        <x-dashboard.field name="fee_public" label="Biaya umum & anggota skors (Rp)" type="number" min="0" :value="(int) $event->fee_public" required />
                        <x-dashboard.field name="thumbnail" label="Poster (maks 3 MB)" type="file" accept="image/*" />
                        @if ($event->thumbnail)<img src="{{ Storage::disk('public')->url($event->thumbnail) }}" alt="" class="w-100 rounded">@endif
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
                </div>
            </div>
        </div>
    </form>
    <x-slot name="javascript">@include('dashboard.partials.tinymce')</x-slot>
</x-dashboard.page>
