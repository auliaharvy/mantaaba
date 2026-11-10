@php($editing = $member->exists)
<x-dashboard.page :title="$editing ? 'Ubah Anggota' : 'Tambah Anggota'" :crumbs="['Anggota', 'Data Anggota', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('member.update', $member) : route('member.store') }}" enctype="multipart/form-data">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="row g-5">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Keanggotaan {{ $editing ? '· ' . $member->member_number : '' }}</div></div>
                    <div class="card-body row">
                        <div class="col-12"><x-dashboard.field name="name" label="Nama lengkap" :value="$member->name" required /></div>
                        <div class="col-md-6"><x-dashboard.field name="category" label="Kategori" type="select" :options="App\Models\Member::CATEGORIES" :value="$member->category" required /></div>
                        @unless ($editing)
                            <div class="col-md-6"><x-dashboard.field name="status" label="Status" type="select" :options="App\Models\Member::STATUSES" :value="$member->status" required /></div>
                        @endunless
                        <div class="col-md-6"><x-dashboard.field name="phase" label="Fase (siswa/alumni)" type="select" :options="App\Models\Member::PHASES" :value="$member->phase" placeholder="—" help="Fase baru tercatat otomatis di Perjalanan." /></div>
                        <div class="col-md-6"><x-dashboard.field name="batch_id" label="Angkatan" type="select" :options="$batches" :value="$member->batch_id" placeholder="—" /></div>
                        <div class="col-md-6"><x-dashboard.field name="joined_at" label="Tanggal bergabung" type="date" :value="$member->joined_at" /></div>
                        @unless ($editing)
                            <div class="col-md-6"><x-dashboard.field name="email" label="Email akun (opsional)" type="email" help="Diisi bila anggota perlu login. Email yang sudah terdaftar tanpa data anggota akan ditautkan." /></div>
                        @endunless
                        <div class="col-md-6">
                            <x-dashboard.field name="photo" label="Foto" type="file" accept="image/*" />
                            @if ($member->photoUrl())<img src="{{ $member->photoUrl() }}" alt="" class="h-75px rounded mb-6">@endif
                        </div>
                        <div class="col-12"><x-dashboard.field name="notes" label="Catatan" type="textarea" :value="$member->notes" /></div>
                        <div class="col-12 form-check form-switch ms-3">
                            <input class="form-check-input" type="checkbox" id="show_on_site" name="show_on_site" value="1" @checked(old('show_on_site', $member->show_on_site))>
                            <label class="form-check-label" for="show_on_site">Izinkan nama & foto tampil di website</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header"><div class="card-title fs-4 fw-bolder">Data pribadi</div></div>
                    <div class="card-body row">
                        @if ($private)
                            <div class="col-md-6"><x-dashboard.field name="nik" label="NIK" :value="$member->nik" maxlength="16" /></div>
                            <div class="col-md-6"><x-dashboard.field name="gender" label="Jenis kelamin" type="select" :options="App\Models\Applicant::GENDERS" :value="$member->gender" placeholder="—" /></div>
                            <div class="col-md-6"><x-dashboard.field name="birth_place" label="Tempat lahir" :value="$member->birth_place" /></div>
                            <div class="col-md-6"><x-dashboard.field name="birth_date" label="Tanggal lahir" type="date" :value="$member->birth_date" /></div>
                            <div class="col-md-6"><x-dashboard.field name="phone" label="Telepon / WA" :value="$member->phone" /></div>
                            <div class="col-md-6"><x-dashboard.field name="address" label="Alamat" :value="$member->address" /></div>
                            <div class="col-md-4"><x-dashboard.field name="village" label="Desa" :value="$member->village" /></div>
                            <div class="col-md-4"><x-dashboard.field name="district" label="Kecamatan" :value="$member->district" /></div>
                            <div class="col-md-4"><x-dashboard.field name="regency" label="Kabupaten" :value="$member->regency" /></div>
                            <div class="col-md-4"><x-dashboard.field name="father_name" label="Nama ayah" :value="$member->father_name" /></div>
                            <div class="col-md-4"><x-dashboard.field name="mother_name" label="Nama ibu" :value="$member->mother_name" /></div>
                            <div class="col-md-4"><x-dashboard.field name="guardian_name" label="Nama wali" :value="$member->guardian_name" /></div>
                            <div class="col-md-4"><x-dashboard.field name="guardian_phone" label="WA orang tua/wali" :value="$member->guardian_phone" /></div>
                            <div class="col-md-4"><x-dashboard.field name="family_status" label="Status keluarga" type="select" :options="App\Models\Applicant::FAMILY_STATUSES" :value="$member->family_status" placeholder="—" /></div>
                            <div class="col-md-4"><x-dashboard.field name="economic_status" label="Ekonomi" type="select" :options="App\Models\Applicant::ECONOMIC_STATUSES" :value="$member->economic_status" placeholder="—" /></div>
                        @else
                            <p class="text-muted">Data pribadi hanya dapat diubah oleh pengurus dengan hak "Lihat data pribadi".</p>
                        @endif
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ $editing ? route('member.show', $member) : route('member.index') }}" class="btn btn-sm btn-light">Batal</a>
                        <button class="btn btn-sm btn-primary" type="submit">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-dashboard.page>
