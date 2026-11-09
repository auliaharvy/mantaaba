<x-dashboard.page title="Profil Saya" :crumbs="['Profil Saya']">
    <div class="row g-5">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="symbol symbol-100px symbol-circle border mb-5">
                        <img alt="" src="{{ $user->photo && Storage::disk('public')->exists('user/' . $user->photo) ? Storage::disk('public')->url('user/' . $user->photo) : asset('media/avatars/blank.png') }}">
                    </div>
                    <div class="fs-3 fw-bolder mb-2">{{ $user->name }}</div>
                    <div class="mb-6"><x-dashboard.role-badge :user="$user" class="badge-lg" /></div>
                    <div class="text-start fs-6">
                        <div class="text-muted">Email</div><div class="mb-3">{{ $user->email }}</div>
                        <div class="text-muted">Telepon</div><div class="mb-3">{{ $user->phone_number ?: '–' }}</div>
                        <div class="text-muted">Tempat, tanggal lahir</div><div class="mb-3">{{ collect([$user->birthplace, $user->date_of_birth?->translatedFormat('j F Y')])->filter()->implode(', ') ?: '–' }}</div>
                        <div class="text-muted">Alamat</div><div>{{ $user->address ?: '–' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-bold mb-6">
                <li class="nav-item"><a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#tab_agenda">Agenda</a></li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#tab_profil">Ubah Profil</a></li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#tab_keamanan">Keamanan</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab_agenda" role="tabpanel">@include('dashboard.profile.training')</div>

                <div class="tab-pane fade" id="tab_profil" role="tabpanel">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card">
                        @csrf @method('PATCH')
                        <div class="card-header"><div class="card-title fs-4 fw-bolder">Ubah Profil</div></div>
                        <div class="card-body row">
                            <div class="col-12"><x-dashboard.field name="photo" label="Foto (maks. 2 MB)" type="file" accept="image/*" /></div>
                            <div class="col-md-6"><x-dashboard.field name="name" label="Nama lengkap" :value="$user->name" required /></div>
                            <div class="col-md-6"><x-dashboard.field name="gender" label="Jenis kelamin" type="select" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" :value="$user->gender" /></div>
                            <div class="col-md-6"><x-dashboard.field name="birthplace" label="Tempat lahir" :value="$user->birthplace" /></div>
                            <div class="col-md-6"><x-dashboard.field name="date_of_birth" label="Tanggal lahir" type="date" :value="$user->date_of_birth" /></div>
                            <div class="col-md-6"><x-dashboard.field name="phone_number" label="Telepon / WhatsApp" :value="$user->phone_number" /></div>
                            <div class="col-md-6"><x-dashboard.field name="email" label="Email" type="email" :value="$user->email" required /></div>
                            <div class="col-12"><x-dashboard.field name="address" label="Alamat" type="textarea" :value="$user->address" /></div>
                        </div>
                        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Simpan</button></div>
                    </form>
                </div>

                <div class="tab-pane fade" id="tab_keamanan" role="tabpanel">
                    <form method="POST" action="{{ route('profile.password') }}" class="card">
                        @csrf @method('PATCH')
                        <div class="card-header"><div class="card-title fs-4 fw-bolder">Ganti Password</div></div>
                        <div class="card-body row">
                            <div class="col-md-6"><x-dashboard.field name="password" label="Password baru (min. 8 karakter)" type="password" required autocomplete="new-password" /></div>
                            <div class="col-md-6"><x-dashboard.field name="password_confirmation" label="Ulangi password baru" type="password" required autocomplete="new-password" /></div>
                        </div>
                        <div class="card-footer text-end"><button class="btn btn-sm btn-primary" type="submit">Ganti password</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-dashboard.page>
