{{-- KUK (kriteria unjuk kerja) satu modul/elemen. $module --}}
@if ($module->criteria)
    <details class="mb-3 fs-7">
        <summary class="text-primary fw-bold cursor-pointer">Kriteria unjuk kerja ({{ count($module->criteria) }} KUK)</summary>
        <ul class="list-unstyled mt-2 mb-0 ps-2">
            @foreach ($module->criteria as $kuk)<li class="d-flex gap-2 mb-1"><span class="text-muted text-nowrap">{{ $kuk['no'] }}</span><span>{{ $kuk['text'] }}</span></li>@endforeach
        </ul>
    </details>
@endif
