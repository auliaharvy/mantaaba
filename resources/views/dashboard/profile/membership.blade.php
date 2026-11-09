{{-- Profil -> Keanggotaan (PRD 8.11): read-only untuk anggota sendiri --}}
<div class="card mb-5">
    <div class="card-header">
        <div class="card-title fs-3 fw-bolder">Keanggotaan</div>
        <div class="card-toolbar">
            <a class="btn btn-sm btn-light-primary" href="{{ App\Models\Setting::current()->url_member_service }}" target="_blank" rel="noopener">
                Layanan Anggota (perpanjangan KTA, legalisir SK) <i class="fa fa-external-link-alt ms-1"></i>
            </a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4 text-muted">Nomor anggota</dt><dd class="col-sm-8">{{ $member->member_number }}</dd>
            <dt class="col-sm-4 text-muted">Jenis</dt><dd class="col-sm-8">{{ App\Models\Member::TYPES[$member->membership_type] }}</dd>
            <dt class="col-sm-4 text-muted">Pengurus Cabang</dt><dd class="col-sm-8">{{ $member->organization->name }}</dd>
            <dt class="col-sm-4 text-muted">Sertifikasi</dt><dd class="col-sm-8">{{ $member->certification ?? '-' }}</dd>
            <dt class="col-sm-4 text-muted">Status keanggotaan</dt><dd class="col-sm-8">{{ App\Models\Member::STATUSES[$member->status] }}</dd>
        </dl>
    </div>
</div>
@include('dashboard.membership.dues', ['readonly' => true, 'defaultDues' => null])
