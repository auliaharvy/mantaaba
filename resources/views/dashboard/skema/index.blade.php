<x-dashboard.page title="Skema & Materi" :crumbs="['Pembelajaran', 'Skema & Materi']">
    <div class="d-flex justify-content-end mb-5">
        @can('learning-create')<a href="{{ route('skema.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah Skema</a>@endcan
    </div>
    <div class="row g-5">
        @forelse ($skemas as $skema)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="badge badge-primary fs-6">{{ $skema->code }}</span>
                            <span class="text-muted fs-7">{{ $skema->month_label }}</span>
                            @unless ($skema->is_public)<span class="badge badge-light ms-auto">tidak publik</span>@endunless
                        </div>
                        <div class="fs-4 fw-bolder mb-2">{{ $skema->name }}</div>
                        <div class="text-muted fs-7 mb-4">{{ Str::limit($skema->description, 140) }}</div>
                        <div class="fs-7">{{ $skema->units_count ? $skema->units_count . ' unit kompetensi · ' : '' }}{{ $skema->modules_count }} modul · {{ $skema->lessons_count }} materi · {{ $skema->enrollments_count }} siswa · lulus ≥ {{ $skema->passScore() }}</div>
                    </div>
                    <div class="card-footer d-flex gap-2 justify-content-end">
                        <a href="{{ route('skema.show', $skema) }}" class="btn btn-sm btn-light-primary">Susun materi</a>
                        @can('learning-update')<a href="{{ route('skema.edit', $skema) }}" class="btn btn-sm btn-light">Ubah</a>@endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-muted">Belum ada skema.</div></div></div>
        @endforelse
    </div>
</x-dashboard.page>
