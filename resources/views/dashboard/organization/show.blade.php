<x-dashboard.page :title="$organization->name" :crumbs="['Organisasi', 'Pengurus Cabang', 'Rincian']">
    <div class="row g-5">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <div class="card-title fs-4 fw-bolder">Profil</div>
                    <div class="card-toolbar gap-2">
                        @can('update', $organization)
                            <a class="btn btn-sm btn-light-success" href="{{ route('organization.edit', $organization) }}"><i class="fa fa-pen"></i> Ubah</a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">Jenis</dt><dd class="col-7">{{ App\Models\Organization::TYPES[$organization->type] }}</dd>
                        <dt class="col-5 text-muted">Kode</dt><dd class="col-7">{{ $organization->code ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Kota/Kab.</dt><dd class="col-7">{{ $organization->city ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Telepon</dt><dd class="col-7">{{ $organization->phone ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Email</dt><dd class="col-7">{{ $organization->email ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Alamat</dt><dd class="col-7">{{ $organization->address ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Status</dt><dd class="col-7">{{ $organization->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
                        <dt class="col-5 text-muted">Anggota aktif</dt><dd class="col-7">{{ $organization->active_members_count }} dari {{ $organization->members_count }}</dd>
                    </dl>
                    @can('member-view')
                        <a class="btn btn-sm btn-light-primary mt-5" href="{{ route('membership.index', ['organization_id' => $organization->id]) }}">Lihat anggota PC ini</a>
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <div class="card-title fs-4 fw-bolder">Pengurus {{ $board ? 'Periode ' . $board->name : '' }}</div>
                    @can('board-view')
                        <div class="card-toolbar">
                            <a class="btn btn-sm btn-light-primary" href="{{ route('board.index', ['organization_id' => $organization->id]) }}">Kelola kepengurusan</a>
                        </div>
                    @endcan
                </div>
                <div class="card-body">
                    @if ($board)
                        @include('dashboard.board.members-list', ['period' => $board])
                    @else
                        <div class="text-muted">Belum ada periode kepengurusan aktif.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-dashboard.page>
