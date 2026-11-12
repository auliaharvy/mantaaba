{{-- Ringkasan modul: pintasan submenu, kartu angka & grafik sesuai permission; modul "Saya" berisi ringkasan pribadi anggota. --}}
<x-dashboard.page :title="$module['title']" :crumbs="['Modul', $module['title']]">
    <div class="row g-4 mb-5">
        @foreach ($module['items'] as $item)
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="card card-flush h-100 hoverable">
                    <div class="card-body d-flex align-items-center gap-3 py-4">
                        <span class="symbol symbol-40px"><span class="symbol-label bg-light-primary"><i class="fa {{ $item['icon'] }} fs-3 text-primary"></i></span></span>
                        <span class="fw-bold text-dark">{{ $item['title'] }}</span>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @if ($todayDuties->isNotEmpty())
        <div class="card mb-5">
            <div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder"><i class="fa fa-broom me-2"></i> Piket hari ini</div>
                <div class="card-toolbar"><a href="{{ route('my-duty.index') }}" class="btn btn-sm btn-light-primary">Buka ceklis</a></div></div>
            <div class="card-body py-3">
                @foreach ($todayDuties as $duty)
                    <div class="d-flex gap-3 py-2"><span class="fw-bold flex-grow-1">{{ $duty->area->name }} · {{ App\Models\Duty::SHIFTS[$duty->shift] }}</span>
                        <span class="badge badge-light-{{ in_array($duty->status, App\Models\Duty::DONE) ? 'success' : ($duty->status === 'terlewat' ? 'danger' : 'primary') }}">{{ App\Models\Duty::STATUSES[$duty->status] }}</span></div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($cards)
        <div class="row g-5 mb-5">
            @foreach ($cards as [$label, $value, $icon, $url])
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ $url }}" class="card card-flush h-100 hoverable">
                        <div class="card-body d-flex align-items-center gap-4">
                            <i class="fa {{ $icon }} fs-1 text-primary"></i>
                            <div><div class="{{ is_numeric($value) ? 'fs-2hx' : 'fs-3' }} fw-bolder text-dark text-nowrap">{{ is_numeric($value) ? number_format($value, 0, ',', '.') : $value }}</div><div class="text-muted">{{ $label }}</div></div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    @if ($learning)
        <div class="row g-5 mb-5">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder"><i class="fa fa-book-open me-2"></i> Progres belajar</div>
                        <div class="card-toolbar"><a href="{{ route('learn.index') }}" class="btn btn-sm btn-light-primary">Belajar</a></div></div>
                    <div class="card-body py-3">
                        @forelse ($learning['skemas'] as $s)
                            <div class="d-flex align-items-center gap-3 py-2">
                                <span class="w-150px fw-bold text-truncate">{{ $s['enrollment']->skema->name }}</span>
                                <div class="progress h-8px flex-grow-1"><div class="progress-bar {{ $s['passed'] ? 'bg-success' : 'bg-primary' }}" style="width: {{ $s['percent'] }}%"></div></div>
                                <span class="fs-8 w-90px text-end">{{ $s['passed'] ? '🏅 Lulus' : App\Models\SkemaEnrollment::STATUSES[$s['enrollment']->status] . ' · ' . $s['percent'] . '%' }}</span>
                            </div>
                        @empty
                            <div class="text-muted">Belum terdaftar di skema.</div>
                        @endforelse
                        @if ($learning['next'])<a href="{{ route('learn.lesson', $learning['next']) }}" class="btn btn-sm btn-primary mt-3">Lanjut: {{ $learning['next']->title }}</a>@endif
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder">Perlu perhatian</div></div>
                    <div class="card-body py-3 fs-7">
                        @foreach ($learning['due'] as $d)
                            <div class="py-1"><i class="fa fa-clock {{ $d['date']->isPast() ? 'text-danger' : 'text-warning' }} me-1"></i> <a href="{{ route('learn.lesson', $d['lesson']) }}">{{ $d['lesson']->title }}</a> · tenggat {{ $d['date']->translatedFormat('j M') }}</div>
                        @endforeach
                        @foreach ($learning['rejected'] as $j)
                            <div class="py-1"><i class="fa fa-times-circle text-danger me-1"></i> Voucher ditolak: <a href="{{ route('journal.show', $j) }}">{{ $j->description }}</a> — {{ $j->reject_reason }}</div>
                        @endforeach
                        <div class="py-1"><i class="fa fa-certificate text-primary me-1"></i> <a href="{{ route('certificate.index') }}">{{ $learning['certificates'] }} sertifikat</a></div>
                        @if ($learning['due']->isEmpty() && $learning['rejected']->isEmpty())<div class="text-muted py-1">Tidak ada tugas yang mendekati tenggat.</div>@endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($charts)
        <div class="row g-5 mb-5">
            @isset($charts['finance'])<div class="col-xl-6"><div class="card h-100"><div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder">Penerimaan vs beban 12 bulan</div></div><div class="card-body"><div id="chart-finance" style="height: 280px"></div></div></div></div>@endisset
            @isset($charts['kitchen'])<div class="col-xl-6"><div class="card h-100"><div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder">Biaya dapur 30 hari</div></div><div class="card-body"><div id="chart-kitchen" style="height: 280px"></div></div></div></div>@endisset
            @isset($charts['budget'])<div class="col-xl-6"><div class="card h-100"><div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder">Realisasi anggaran {{ now()->year }}</div></div><div class="card-body"><div id="chart-budget" style="height: 280px"></div></div></div></div>@endisset
        </div>
    @endif

    @if ($event)
    <div class="card">
        <div class="card-header"><div class="card-title fs-4 fw-bolder">Agenda terdekat</div></div>
        <div class="card-body">
                <div class="fw-bolder fs-5">{{ $event->title }}</div>
                <div class="text-muted">{{ $event->starts_at?->translatedFormat('l, j F Y H:i') }} · {{ $event->location ?: App\Models\Event::MODES[$event->mode] }}</div>
                <a href="{{ route('site.training.show', $event->slug) }}" class="btn btn-sm btn-light-primary mt-4">Lihat agenda</a>
        </div>
    </div>
    @endif
    @if ($charts)
        <x-slot name="javascript"><script>
            (function () {
                if (typeof ApexCharts === 'undefined') return;
                const charts = @json($charts);
                const rp = (v) => v === null ? '-' : 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
                // data kosong membuat ApexCharts memberi sumbu Infinity; jangan tampilkan "∞"
                const compact = (v) => Number.isFinite(v) ? new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(v) : '';
                const brand = getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() || '#1E4D3A';
                const draw = (id, options) => { const el = document.getElementById(id); if (el) new ApexCharts(el, Object.assign({ chart: { height: 280, toolbar: { show: false }, fontFamily: 'inherit' } }, options)).render(); };
                if (charts.finance) draw('chart-finance', { chart: { type: 'bar', height: 280, toolbar: { show: false } }, series: [{ name: 'Penerimaan', data: charts.finance.revenue }, { name: 'Beban', data: charts.finance.expense }],
                    xaxis: { categories: charts.finance.labels }, colors: ['#C9A04A', '#A84B24'], dataLabels: { enabled: false }, yaxis: { labels: { formatter: compact } }, tooltip: { y: { formatter: rp } } });
                if (charts.kitchen) draw('chart-kitchen', { chart: { type: 'line', height: 280, toolbar: { show: false } }, series: [{ name: 'Biaya hari itu', type: 'column', data: charts.kitchen.cost }, { name: 'Per orang', type: 'line', data: charts.kitchen.per_person }],
                    xaxis: { categories: charts.kitchen.labels, labels: { rotate: -45 } }, colors: [brand, '#C9A04A'], dataLabels: { enabled: false }, stroke: { width: [0, 3] }, tooltip: { y: { formatter: rp } },
                    yaxis: [{ labels: { formatter: compact } }, { opposite: true, labels: { formatter: compact } }] });
                if (charts.budget) draw('chart-budget', { chart: { type: 'bar', height: 280, toolbar: { show: false } }, series: [{ name: 'Serapan', data: charts.budget.percent }], plotOptions: { bar: { horizontal: true } },
                    xaxis: { categories: charts.budget.labels, max: Math.max(100, ...charts.budget.percent) }, colors: [brand], dataLabels: { enabled: true, formatter: (v) => v + '%' }, tooltip: { y: { formatter: (v) => v + '%' } } });
            })();
        </script></x-slot>
    @endif
</x-dashboard.page>
