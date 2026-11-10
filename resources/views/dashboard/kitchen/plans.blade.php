@php
    $canEdit = auth()->user()->can('kitchen-log');
    $menuNames = $menus->pluck('name', 'id');
@endphp
<x-dashboard.page title="Rencana Menu Mingguan" :crumbs="['Operasional', 'Dapur', 'Rencana']">
    @include('dashboard.kitchen.nav')
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <a href="{{ route('kitchen.plans', ['minggu' => $start->copy()->subWeek()->toDateString()]) }}" class="btn btn-sm btn-light"><i class="fa fa-chevron-left"></i></a>
            <b>{{ $start->translatedFormat('j M') }} – {{ $start->copy()->addDays(6)->translatedFormat('j M Y') }}</b>
            <a href="{{ route('kitchen.plans', ['minggu' => $start->copy()->addWeek()->toDateString()]) }}" class="btn btn-sm btn-light"><i class="fa fa-chevron-right"></i></a>
            <span class="text-muted fs-7">Porsi bawaan: {{ $residents }} penghuni</span>
            <a href="{{ route('kitchen.needs', ['minggu' => $start->toDateString()]) }}" class="btn btn-sm btn-light-primary ms-auto"><i class="fa fa-calculator"></i> Hitung kebutuhan</a>
        </div>
    </div>
    <form method="POST" action="{{ route('kitchen.plans-save') }}">
        @csrf
        <div class="row g-4">
            @for ($d = 0; $d < 7; $d++)
                @php
                    $date = $start->copy()->addDays($d);
                @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header min-h-45px"><div class="card-title fs-6 fw-bolder {{ $date->isToday() ? 'text-primary' : '' }}">{{ $date->translatedFormat('l, j M') }}</div></div>
                        <div class="card-body py-3">
                            @foreach (App\Models\MealPlan::MEALS as $meal => $label)
                                @php
                                    $plan = $plans[$date->toDateString() . '|' . $meal] ?? null;
                                    $name = "plans[{$date->toDateString()}][{$meal}]";
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between fs-7 fw-bold">{{ $label }}@if ($plan?->status === 'dimasak')<span class="badge badge-light-success">dimasak</span>@endif</div>
                                    @if ($canEdit && $plan?->status !== 'dimasak')
                                        <div class="d-flex gap-2">
                                            <select name="{{ $name }}[menu_ids][]" multiple data-control="select2" data-placeholder="Pilih menu" class="form-select form-select-sm" aria-label="Menu {{ $label }} {{ $date->format('d/m') }}">
                                                @foreach ($menus as $m)<option value="{{ $m->id }}" @selected(in_array($m->id, $plan->menu_ids ?? []))>{{ $m->name }}</option>@endforeach
                                            </select>
                                            <input type="number" min="1" name="{{ $name }}[portions]" value="{{ $plan->planned_portions ?? '' }}" placeholder="{{ $residents }}" class="form-control form-control-sm w-70px" aria-label="Porsi {{ $label }} {{ $date->format('d/m') }}">
                                        </div>
                                    @else
                                        <div class="fs-7 text-muted">{{ $plan ? collect($plan->menu_ids)->map(fn ($id) => $menuNames[$id] ?? null)->filter()->implode(', ') . ' · ' . $plan->planned_portions . ' porsi' : '—' }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endfor
        </div>
        @if ($canEdit)<div class="text-end mt-5"><button class="btn btn-primary">Simpan rencana</button></div>@endif
    </form>
</x-dashboard.page>
