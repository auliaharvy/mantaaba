<x-dashboard.page title="Laporan Keuangan" :crumbs="['Keuangan', 'Laporan Keuangan']">
    <div class="row g-5">
        @foreach ($reports as $key => $label)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('report.show', $key) }}" class="card card-flush h-100 hoverable">
                    <div class="card-body d-flex align-items-center gap-4">
                        <i class="fa {{ ['posisi' => 'fa-balance-scale', 'aktivitas' => 'fa-chart-line', 'aset-neto' => 'fa-layer-group', 'arus-kas' => 'fa-water', 'pusat-biaya' => 'fa-sitemap', 'piutang' => 'fa-hand-holding-usd', 'utang' => 'fa-file-invoice', 'anggaran' => 'fa-coins', 'rasio' => 'fa-percentage', 'jurnal-umum' => 'fa-list', 'transparansi' => 'fa-globe'][$key] }} fs-1 text-primary"></i>
                        <span class="fw-bolder fs-5 text-dark">{{ $label }}</span>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</x-dashboard.page>
