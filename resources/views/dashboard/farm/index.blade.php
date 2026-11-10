<x-dashboard.page title="Kebun & Kolam" :crumbs="['Operasional', 'Kebun & Kolam']">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <div class="btn-group">
                @foreach (['' => 'Semua'] + App\Models\FarmPlot::TYPES as $key => $label)
                    <a href="{{ route('farm.index', $key ? ['tipe' => $key] : []) }}" class="btn btn-sm {{ (string) $type === (string) $key ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="text-muted fs-7">Panen bulan ini: {{ $month->isNotEmpty() ? $month->map(fn ($v, $k) => "{$v} {$k}")->implode(' · ') : 'belum ada' }}</div>
            @can('farm-create')<a href="{{ route('farm.plot-create') }}" class="btn btn-sm btn-primary ms-auto"><i class="fa fa-plus"></i> Petak/kolam</a>@endcan
        </div>
    </div>
    <div class="row g-5">
        @forelse ($plots as $plot)
            @php
                $cycle = $plot->activeCycle;
                $progress = $cycle?->progress();
                $s = $summaries[$plot->id] ?? null;
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header min-h-50px">
                        <div class="card-title fs-6 fw-bolder">{{ $plot->code }} · {{ $plot->name }}</div>
                        <div class="card-toolbar"><span class="badge badge-light">{{ App\Models\FarmPlot::TYPES[$plot->type] }}</span>
                            @can('farm-update')<a href="{{ route('farm.plot-edit', $plot) }}" class="btn btn-sm btn-icon btn-light ms-2" title="Ubah petak"><i class="fa fa-pen"></i></a>@endcan</div>
                    </div>
                    <div class="card-body">
                        @if ($cycle)
                            <a href="{{ route('farm.cycle', $cycle) }}" class="fw-bold fs-5">{{ $cycle->commodity }}{{ $cycle->variety ? ' · ' . $cycle->variety : '' }}</a>
                            <div class="text-muted fs-7 mb-2">{{ App\Models\FarmCycle::STATUSES[$cycle->status] }} · mulai {{ $cycle->started_at->translatedFormat('j M') }}</div>
                            @if ($progress)
                                <div class="fs-8 text-muted">Hari {{ $progress['day'] }}/{{ $progress['total'] }}</div>
                                <div class="progress h-6px mb-2"><div class="progress-bar bg-success" style="width: {{ $progress['percent'] }}%"></div></div>
                            @endif
                            <div class="fs-7">Biaya input {{ App\Support\Money::format(App\Support\Money::decimal($s['cost'])) }}@if ($s['harvest_qty']) · panen {{ App\Services\StockLedger::qty($s['harvest_qty']) }}@endif</div>
                            @if ($s['survival'] !== null)<div class="fs-7">Hidup {{ $s['survival'] }}% ({{ App\Services\StockLedger::qty($s['population']) }} {{ $cycle->initial_unit }})</div>@endif
                            @if ($plot->pic)<div class="fs-7 text-muted">PIC {{ $plot->pic->name }}</div>@endif
                        @else
                            <p class="text-muted mb-2">({{ App\Models\FarmPlot::STATUSES[$plot->status] }})</p>
                            @can('farm-create')<a href="{{ route('farm.cycle-create', $plot) }}" class="btn btn-sm btn-light-primary">Mulai siklus</a>@endcan
                        @endif
                    </div>
                    @if ($cycle)
                        <div class="card-footer py-3 d-flex gap-2">
                            <a href="{{ route('farm.cycle', $cycle) }}#log" class="btn btn-sm btn-light-primary"><i class="fa fa-plus"></i> Catat</a>
                            <a href="{{ route('farm.cycle', $cycle) }}#panen" class="btn btn-sm btn-light-success"><i class="fa fa-shopping-basket"></i> Panen</a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-10">Belum ada petak atau kolam.</div></div></div>
        @endforelse
    </div>
</x-dashboard.page>
