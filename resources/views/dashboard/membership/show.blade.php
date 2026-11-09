@php($user = $member->user)
<x-dashboard.page :title="$user->name" :crumbs="['Organisasi', 'Data Anggota', 'Rincian']">
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-5">
        <a class="btn btn-sm btn-light" href="{{ route('membership.index') }}"><i class="fa fa-arrow-left"></i> Daftar anggota</a>
        @can('update', $member)
            <form method="POST" action="{{ route('membership.activation', $member) }}">
                @csrf
                <button class="btn btn-sm btn-light-info" type="submit"><i class="fa fa-envelope"></i> Kirim email aktivasi</button>
            </form>
            <a class="btn btn-sm btn-light-success" href="{{ route('membership.edit', $member) }}"><i class="fa fa-pen"></i> Ubah</a>
        @endcan
    </div>

    <div class="row g-5">
        <div class="col-lg-4">
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-4 fw-bolder">Data Diri</div></div>
                <div class="card-body">
                    <div class="fw-bolder">Email</div><div class="text-gray-600">{{ $user->email }}</div>
                    <div class="fw-bolder mt-5">Telepon</div><div class="text-gray-600">{{ $user->phone_number ?? '-' }}</div>
                    <div class="fw-bolder mt-5">Tempat, tanggal lahir</div>
                    <div class="text-gray-600">{{ $user->birthplace ?? '-' }}{{ $user->date_of_birth ? ', ' . Carbon\Carbon::parse($user->date_of_birth)->translatedFormat('j F Y') : '' }}</div>
                    <div class="fw-bolder mt-5">Alamat</div><div class="text-gray-600">{{ $user->address ?? '-' }}</div>
                    <div class="fw-bolder mt-5">Akun</div>
                    <div class="text-gray-600"><x-dashboard.role-badge :user="$user" /> {{ $user->is_active ? '' : '(nonaktif)' }}</div>
                    @include('dashboard.partials.user-positions')
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-4 fw-bolder">Keanggotaan</div></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nomor anggota</dt><dd class="col-sm-8">{{ $member->member_number }}</dd>
                        <dt class="col-sm-4 text-muted">PC</dt><dd class="col-sm-8">{{ $member->organization->name }}</dd>
                        <dt class="col-sm-4 text-muted">Jenis</dt><dd class="col-sm-8">{{ App\Models\Member::TYPES[$member->membership_type] }}</dd>
                        <dt class="col-sm-4 text-muted">Status</dt><dd class="col-sm-8">{{ App\Models\Member::STATUSES[$member->status] }}</dd>
                        <dt class="col-sm-4 text-muted">Nomor KIP</dt><dd class="col-sm-8">{{ $member->kip_number ?? '-' }}</dd>
                        <dt class="col-sm-4 text-muted">Sertifikasi</dt><dd class="col-sm-8">{{ $member->certification ?? '-' }}</dd>
                        <dt class="col-sm-4 text-muted">Brevet</dt><dd class="col-sm-8">{{ $member->brevet ? implode(', ', $member->brevet) : '-' }}</dd>
                        <dt class="col-sm-4 text-muted">Kantor/firma</dt><dd class="col-sm-8">{{ $member->office_name ?? '-' }}</dd>
                        <dt class="col-sm-4 text-muted">Bergabung</dt><dd class="col-sm-8">{{ $member->joined_at?->translatedFormat('j F Y') ?? '-' }}</dd>
                        <dt class="col-sm-4 text-muted">Nomor SK</dt><dd class="col-sm-8">{{ $member->sk_number ?? '-' }}</dd>
                        {{-- PRD 8.2: NPWP admin PD & pemilik, NIK hanya admin PD --}}
                        @can('update', $member)
                            <dt class="col-sm-4 text-muted">NPWP</dt><dd class="col-sm-8">{{ $member->npwp ?? '-' }}</dd>
                            <dt class="col-sm-4 text-muted">NIK</dt><dd class="col-sm-8">{{ $member->nik ?? '-' }}</dd>
                            <dt class="col-sm-4 text-muted">Catatan</dt><dd class="col-sm-8">{{ $member->notes ?? '-' }}</dd>
                        @endcan
                    </dl>
                </div>
            </div>

            @include('dashboard.membership.dues')

            <div class="card mb-5">
                <div class="card-header">
                    <div class="card-title fs-4 fw-bolder">Riwayat Event & Jam PPL</div>
                    <div class="card-toolbar">
                        @php($ppl = $user->pplHoursByYear())
                        <span class="badge badge-light-primary fs-7">{{ now()->year }}: {{ rtrim(rtrim(number_format($ppl[now()->year] ?? 0, 2, ',', '.'), '0'), ',') }} jam</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($ppl->isNotEmpty())
                        <div class="text-muted fs-7 mb-4">Rekap per tahun: @foreach ($ppl as $year => $hours){{ $year }} = {{ rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',') }} jam{{ $loop->last ? '' : ' · ' }}@endforeach</div>
                    @endif
                    @forelse ($user->participants->sortByDesc(fn ($p) => $p->event?->starts_at) as $participant)
                        <div class="d-flex justify-content-between border-bottom py-2 gap-3">
                            <span>{{ $participant->event?->title }} <span class="text-muted fs-7">· {{ App\Models\Participant::STATUSES[$participant->status] }}{{ $participant->attended_at ? ' · hadir' : '' }}</span></span>
                            <span class="text-muted text-nowrap">{{ $participant->event?->starts_at?->translatedFormat('j M Y') }}</span>
                        </div>
                    @empty
                        <div class="text-muted">Belum mengikuti event.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-dashboard.page>
