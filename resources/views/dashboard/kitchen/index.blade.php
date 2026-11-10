@php
    $rp = fn ($cents) => App\Support\Money::format(App\Support\Money::decimal($cents));
@endphp
<x-dashboard.page :title="'Dapur · ' . $date->translatedFormat('l, j M Y')" :crumbs="['Operasional', 'Dapur']">
    @include('dashboard.kitchen.nav')
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-5 align-items-center">
            <form method="GET"><input type="date" name="tanggal" value="{{ $date->toDateString() }}" class="form-control form-control-sm" onchange="this.form.submit()" aria-label="Tanggal"></form>
            <div><div class="text-muted fs-8">Biaya hari ini</div><b class="fs-4">{{ $rp($day['cost']) }}</b></div>
            <div><div class="text-muted fs-8">Per orang ({{ $day['residents'] }} penghuni)</div>
                <b class="fs-4 {{ $day['budget'] && $day['per_person'] > $day['budget'] ? 'text-danger' : 'text-success' }}">{{ $day['per_person'] !== null ? $rp($day['per_person']) : '-' }}</b>
                @if ($day['budget'])<span class="text-muted fs-8">/ anggaran {{ $rp($day['budget']) }}</span>@endif</div>
            <div><div class="text-muted fs-8">Kontribusi kebun (nilai pasar)</div><b>{{ $rp($day['garden']) }}</b></div>
            <div class="ms-auto text-muted fs-7">Piket: {{ $duties->pluck('member.name')->filter()->implode(', ') ?: '-' }}</div>
        </div>
    </div>
    <div class="card mb-5">
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0">
                @foreach (App\Models\MealPlan::MEALS as $meal => $label)
                    @php
                        $plan = $plans[$meal] ?? null;
                    @endphp
                    <tr>
                        <td class="fw-bold w-150px">{{ $label }}</td>
                        <td>{{ $plan ? collect($plan->menu_ids)->map(fn ($id) => $menus[$id]->name ?? null)->filter()->implode(', ') : '—' }}</td>
                        <td class="w-100px">{{ $plan ? ($plan->log?->actual_portions ?? $plan->planned_portions) . ' porsi' : '' }}</td>
                        <td class="w-150px">@if ($plan?->log)<span class="badge badge-light-success">Dimasak</span> {{ App\Support\Money::format($plan->log->cost_total) }}@elseif ($plan)<span class="badge badge-light">Rencana</span>@endif</td>
                        <td class="text-end w-100px">@if ($plan && ! $plan->log && auth()->user()->can('kitchen-log'))<a href="{{ route('kitchen.cook-form', $plan) }}" class="btn btn-sm btn-primary">Masak</a>@endif</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
    @if ($low->isNotEmpty())
        <div class="alert alert-warning d-flex flex-wrap gap-3 align-items-center">
            <span><b>Stok menipis:</b> {{ $low->map(fn ($i) => $i->name . ' ' . App\Services\StockLedger::qty($i->balance) . ' ' . $i->unit)->implode(' · ') }}</span>
            @can('stock-move')<a href="{{ route('stock.purchase-form', ['gudang' => App\Services\KitchenService::warehouse()->id, 'items' => $low->mapWithKeys(fn ($i) => [$i->id => max(0, (float) $i->min_stock - $i->balance)])->all()]) }}" class="btn btn-sm btn-warning ms-auto">Buat belanja</a>@endcan
        </div>
    @endif
</x-dashboard.page>
