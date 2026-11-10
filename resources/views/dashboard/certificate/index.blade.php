<x-dashboard.page title="Sertifikat" :crumbs="['Sertifikat']">
    @if ($mine->isNotEmpty() || ! $staff)
        <div class="card mb-5">
            <div class="card-header"><div class="card-title fs-4 fw-bolder">Sertifikat Saya</div></div>
            <div class="card-body">
                @forelse ($mine as $c)
                    <div class="d-flex flex-wrap align-items-center gap-3 py-3 border-bottom">
                        <div class="flex-grow-1"><div class="fw-bold">{{ $c->title() }}</div><div class="text-muted fs-7">{{ $c->number }} · {{ $c->issued_at?->translatedFormat('j M Y') ?? 'sedang diproses' }}</div></div>
                        @if ($c->revoked_at)
                            <span class="badge badge-light-danger">Dicabut</span>
                        @elseif ($c->isValid() && $c->file)
                            <a href="{{ route('site.certificate', $c->verification_code) }}" target="_blank" class="btn btn-sm btn-light">Verifikasi</a>
                            <a href="{{ route('certificate.download', $c) }}" class="btn btn-sm btn-success"><i class="fa fa-download"></i> Unduh PDF</a>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada sertifikat. Sertifikat terbit setelah Anda lulus satu skema.</p>
                @endforelse
            </div>
        </div>
    @endif
    @if ($staff)
        <div class="card">
            <div class="card-header"><div class="card-title fs-4 fw-bolder">Semua Sertifikat</div></div>
            <div class="card-body p-lg-8">
                <x-dashboard.datatable :url="route('certificate.datatable')" :order="[[1, 'desc']]" :columns="[
                    ['data' => 'DT_RowIndex', 'title' => 'No.'],
                    ['data' => 'number', 'title' => 'Nomor'],
                    ['data' => 'member.name', 'name' => 'member.name', 'title' => 'Anggota', 'orderable' => false],
                    ['data' => 'series', 'title' => 'Seri'],
                    ['data' => 'issued_at', 'title' => 'Terbit', 'searchable' => false],
                    ['data' => 'state', 'title' => 'Status', 'orderable' => false, 'searchable' => false],
                    ['data' => 'action', 'title' => ''],
                ]" />
            </div>
        </div>
    @endif
</x-dashboard.page>
