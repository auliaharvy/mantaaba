{{-- Detail unit kompetensi: deskripsi, penilaian 4 dimensi, konteks praktik. $unit, $collapseId --}}
<div class="collapse" id="{{ $collapseId }}">
    <div class="border rounded p-4 mb-4 bg-light fs-7">
        @if ($unit->description)<p class="mb-4">{{ $unit->description }}</p>@endif
        @if ($unit->assessment)
            <div class="fw-bolder mb-2">Penilaian 4 dimensi</div>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-row-bordered mb-0 bg-white">
                    @foreach ($unit->assessment as $a)
                        <tr><td class="fw-bold text-nowrap ps-3">{{ $a['label'] ?? $a['dimension'] }}</td><td class="text-nowrap">{{ $a['weight'] }}%</td>
                            <td>{{ $a['indicator'] ?? '' }}@if (! empty($a['evidence']))<div class="text-muted">Bukti/metode: {{ $a['evidence'] }}</div>@endif</td></tr>
                    @endforeach
                </table>
            </div>
        @endif
        @foreach ($unit->context ?? [] as $label => $text)
            @if ($text)<div class="mb-2"><b>{{ $label }}:</b> {{ $text }}</div>@endif
        @endforeach
    </div>
</div>
