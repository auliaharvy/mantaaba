<x-dashboard.page title="Profil Saya" :crumbs="['Profil Saya']">
    <div class="row g-5">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="symbol symbol-100px symbol-circle border mb-5">
                        <img alt="" src="{{ $member?->photoUrl() ?? ($user->photo && Storage::disk('public')->exists('user/' . $user->photo) ? Storage::disk('public')->url('user/' . $user->photo) : asset('media/avatars/blank.png')) }}">
                    </div>
                    <div class="fs-3 fw-bolder mb-2">{{ $user->name }}</div>
                    <div class="mb-6"><x-dashboard.role-badge :user="$user" class="badge-lg" /></div>
                    @if ($member)
                        <div class="text-start fs-6 mb-6 border-bottom pb-6">
                            <div class="text-muted">Nomor anggota</div><div class="mb-3 fw-bolder">{{ $member->member_number }}</div>
                            <div class="text-muted">Keanggotaan</div><div class="mb-3">{{ App\Models\Member::CATEGORIES[$member->category] }} · {{ App\Models\Member::STATUSES[$member->status] }}{{ $member->phase ? ' · Fase ' . App\Models\Member::PHASES[$member->phase] : '' }}</div>
                            @if ($member->batch)<div class="text-muted">Angkatan</div><div class="mb-3">{{ $member->batch->name }}</div>@endif
                        </div>
                    @endif
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
                @if ($member)
                    <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#tab_perjalanan">Perjalanan</a></li>
                    <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#tab_pembinaan">Pembinaan</a></li>
                    <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#tab_kesehatan">Kesehatan</a></li>
                @endif
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#tab_profil">Ubah Profil</a></li>
                <li class="nav-item"><a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#tab_keamanan">Keamanan</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab_agenda" role="tabpanel">@include('dashboard.profile.training')</div>
                @if ($member)
                    <div class="tab-pane fade" id="tab_perjalanan" role="tabpanel"><div class="card"><div class="card-body">
                        @forelse ($member->journeys as $journey)
                            <div class="py-3 border-bottom"><span class="badge badge-light-primary me-2">{{ App\Models\MemberJourney::TYPES[$journey->type] }}</span><span class="fw-bold">{{ $journey->title }}{{ $journey->businessUnit ? ' · ' . $journey->businessUnit->name : '' }}</span>
                                <div class="text-muted fs-7">{{ $journey->started_at->translatedFormat('j M Y') }} – {{ $journey->ended_at?->translatedFormat('j M Y') ?? 'sekarang' }}</div></div>
                        @empty
                            <p class="text-muted mb-0">Belum ada catatan perjalanan.</p>
                        @endforelse
                    </div></div></div>
                    <div class="tab-pane fade" id="tab_pembinaan" role="tabpanel"><div class="card"><div class="card-body">
                        @forelse ($notes as $note)
                            <div class="py-3 border-bottom"><span class="badge badge-{{ App\Models\CoachingNote::TYPE_COLORS[$note->type] }} me-2">{{ App\Models\CoachingNote::TYPES[$note->type] }}</span><span class="text-muted fs-7">{{ $note->date->translatedFormat('j M Y') }} · {{ App\Models\CoachingNote::DIMENSIONS[$note->dimension] }}</span><div>{{ $note->body }}</div></div>
                        @empty
                            <p class="text-muted mb-0">Belum ada catatan pembinaan untuk Anda.</p>
                        @endforelse
                    </div></div></div>
                    <div class="tab-pane fade" id="tab_kesehatan" role="tabpanel"><div class="card"><div class="card-body">
                        @forelse ($health as $record)
                            <div class="py-3 border-bottom"><span class="fw-bold">{{ $record->complaint }}</span><div class="text-muted fs-7">{{ $record->date->translatedFormat('j M Y') }} · {{ App\Models\HealthRecord::ACTIONS[$record->action] }}{{ $record->facility ? ' · ' . $record->facility : '' }}</div></div>
                        @empty
                            <p class="text-muted mb-0">Belum ada catatan kesehatan.</p>
                        @endforelse
                    </div></div></div>
                @endif

                <div class="tab-pane fade" id="tab_profil" role="tabpanel">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card">
                        @csrf @method('PATCH')
                        <div class="card-header"><div class="card-title fs-4 fw-bolder">Ubah Profil</div></div>
                        <div class="card-body row">
                            <div class="col-12"><x-dashboard.field name="photo" label="Foto (maks. 2 MB)" type="file" accept="image/*" /></div>
                            @unless ($member)
                                <div class="col-md-6"><x-dashboard.field name="name" label="Nama lengkap" :value="$user->name" required /></div>
                                <div class="col-md-6"><x-dashboard.field name="gender" label="Jenis kelamin" type="select" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" :value="$user->gender" /></div>
                                <div class="col-md-6"><x-dashboard.field name="birthplace" label="Tempat lahir" :value="$user->birthplace" /></div>
                                <div class="col-md-6"><x-dashboard.field name="date_of_birth" label="Tanggal lahir" type="date" :value="$user->date_of_birth" /></div>
                                <div class="col-md-6"><x-dashboard.field name="email" label="Email" type="email" :value="$user->email" required /></div>
                            @endunless
                            <div class="col-md-6"><x-dashboard.field name="phone_number" label="Telepon / WhatsApp" :value="$member?->phone ?? $user->phone_number" /></div>
                            <div class="col-12"><x-dashboard.field name="address" label="Alamat" type="textarea" :value="$member?->address ?? $user->address" /></div>
                            @if ($member)<div class="col-12 text-muted fs-7">Nama, email, dan data lain diubah oleh pengurus.</div>@endif
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
