<x-dashboard.page :title="$skema->name" :crumbs="['Belajar', $skema->code]">
    <div class="card mb-5">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-3"><div><span class="badge badge-primary me-2">{{ $skema->code }}</span><span class="text-muted">{{ $skema->month_label }}</span></div>
                <a href="{{ route('learn.index') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i> Semua skema</a></div>
            @if ($skema->objectives)<div class="mt-4">{!! $skema->objectives !!}</div>@endif
        </div>
    </div>
    @foreach ($skema->modules as $module)
        <div class="card mb-4">
            <div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder">{{ $module->code && $module->elements ? $module->code . ' · ' : '' }}{{ $module->title }}</div></div>
            <div class="card-body py-2">
                <div class="pt-3">@include('dashboard.skema.unit-detail', ['module' => $module, 'collapseId' => 'unit-' . $module->id])</div>
                @forelse ($module->lessons as $lesson)
                    @php($p = $progress[$lesson->id] ?? null)
                    <a href="{{ route('learn.lesson', $lesson) }}" class="d-flex align-items-center gap-3 py-3 border-bottom text-gray-800">
                        <span class="fs-4 {{ $p?->completed_at ? 'text-success' : 'text-gray-400' }}">{!! $p?->completed_at ? '&#10003;' : '&#9675;' !!}</span>
                        <span class="flex-grow-1">{{ $lesson->title }}</span>
                        <span class="badge badge-light-{{ $lesson->isAssignment() ? 'warning' : 'primary' }}">{{ App\Models\Lesson::TYPES[$lesson->type] }}</span>
                        @if ($lesson->isAssignment())<span class="fs-8 text-muted">{{ $p?->reviewed_at ? 'Nilai ' . $p->score : ($p?->submitted_at ? 'Menunggu nilai' : 'Belum dikirim') }}</span>@endif
                    </a>
                @empty
                    <p class="text-muted my-3">Materi segera tersedia.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</x-dashboard.page>
