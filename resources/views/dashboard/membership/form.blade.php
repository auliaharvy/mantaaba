@php($editing = $member->exists)
<x-dashboard.page :title="$editing ? 'Ubah Anggota' : 'Tambah Anggota'" :crumbs="['Organisasi', 'Data Anggota', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('membership.update', $member) : route('membership.store') }}" autocomplete="off">
        @csrf
        @if ($editing) @method('PATCH') @endif
        <div class="card mb-5">
            <div class="card-header">
                <div class="card-title fs-3 fw-bolder">Data Diri</div>
                <div class="card-toolbar">
                    <a class="btn btn-sm btn-light-primary" href="{{ $editing ? route('membership.show', $member) : route('membership.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
                </div>
            </div>
            <div class="card-body row">
                @unless ($editing)
                    <div class="col-12">
                        <div class="alert alert-light-primary py-3">Bila email sudah dipakai akun <strong>Umum</strong>, akun itu dinaikkan menjadi Anggota (tidak dibuat akun baru).</div>
                    </div>
                @endunless
                <div class="col-md-6"><x-dashboard.field name="name" label="Nama lengkap" :value="$member->user?->name" required /></div>
                <div class="col-md-6"><x-dashboard.field name="email" label="Email" type="email" :value="$member->user?->email" required /></div>
                <div class="col-md-4"><x-dashboard.field name="phone_number" label="Telepon" :value="$member->user?->phone_number" /></div>
                <div class="col-md-4"><x-dashboard.field name="gender" label="Jenis kelamin" type="select" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" :value="$member->user?->gender" /></div>
                <div class="col-md-4"><x-dashboard.field name="date_of_birth" label="Tanggal lahir" type="date" :value="$member->user?->date_of_birth" /></div>
                <div class="col-md-4"><x-dashboard.field name="birthplace" label="Tempat lahir" :value="$member->user?->birthplace" /></div>
                <div class="col-md-8"><x-dashboard.field name="address" label="Alamat" :value="$member->user?->address" /></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title fs-3 fw-bolder">Keanggotaan</div></div>
            <div class="card-body row">
                <div class="col-md-6"><x-dashboard.field name="organization_id" label="Pengurus Cabang (PC)" type="select" :options="$organizations" :value="$member->organization_id" required /></div>
                <div class="col-md-3"><x-dashboard.field name="member_number" label="Nomor anggota" :value="$member->member_number" required /></div>
                <div class="col-md-3"><x-dashboard.field name="membership_type" label="Jenis anggota" type="select" :options="App\Models\Member::TYPES" :value="$member->membership_type" required /></div>
                <div class="col-md-4"><x-dashboard.field name="kip_number" label="Nomor KIP (izin praktik)" :value="$member->kip_number" /></div>
                <div class="col-md-4"><x-dashboard.field name="certification" label="Sertifikasi konsultan" type="select" :options="App\Models\Member::CERTIFICATIONS" :value="$member->certification" placeholder="— Tidak ada —" /></div>
                <div class="col-md-4 mb-6">
                    <label class="form-label">Brevet</label>
                    <div class="d-flex gap-5 pt-2">
                        @foreach (['A', 'B', 'C'] as $level)
                            <label class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="brevet[]" value="{{ $level }}" @checked(in_array($level, old('brevet', $member->brevet ?? [])))>
                                <span class="form-check-label">{{ $level }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-4"><x-dashboard.field name="npwp" label="NPWP" :value="$member->npwp" /></div>
                <div class="col-md-4"><x-dashboard.field name="nik" label="NIK" :value="$member->nik" help="Hanya terlihat oleh admin PD." /></div>
                <div class="col-md-4"><x-dashboard.field name="office_name" label="Kantor/firma" :value="$member->office_name" /></div>
                <div class="col-md-4"><x-dashboard.field name="joined_at" label="Tanggal bergabung" type="date" :value="$member->joined_at" /></div>
                <div class="col-md-4"><x-dashboard.field name="sk_number" label="Nomor SK keanggotaan" :value="$member->sk_number" /></div>
                <div class="col-md-4"><x-dashboard.field name="status" label="Status" type="select" :options="App\Models\Member::STATUSES" :value="$member->status" required help="Berhenti: role akun turun menjadi Umum." /></div>
                <div class="col-12"><x-dashboard.field name="notes" label="Catatan internal" type="textarea" :value="$member->notes" /></div>
                @unless ($editing)
                    <div class="col-12 form-check form-switch ms-3">
                        <input class="form-check-input" type="checkbox" id="send_activation" name="send_activation" value="1" @checked(old('send_activation', true))>
                        <label class="form-check-label" for="send_activation">Kirim email aktivasi sekarang</label>
                    </div>
                @endunless
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
            </div>
        </div>
    </form>
</x-dashboard.page>
