{{-- Detail unit kompetensi: deskripsi, elemen & KUK, penilaian 4 dimensi, konteks praktik. $module, $collapseId --}}
@if ($module->elements)
    <button type="button" class="btn btn-sm btn-light-primary mb-3" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false">
        <i class="fa fa-list-ol"></i> Elemen & KUK ({{ count($module->elements) }} elemen · {{ $module->criteriaCount() }} KUK)
    </button>
    <div class="collapse" id="{{ $collapseId }}">
        <div class="border rounded p-4 mb-4 bg-light fs-7">
            @if ($module->summary)<p class="mb-4">{{ $module->summary }}</p>@endif
            <ol class="ps-4 mb-4">
                @foreach ($module->elements as $element)
                    <li class="mb-3"><b>{{ $element['title'] }}</b>
                        <ul class="list-unstyled mt-1 mb-0">
                            @foreach ($element['criteria'] ?? [] as $kuk)<li class="d-flex gap-2 mb-1"><span class="text-muted text-nowrap">{{ $kuk['no'] }}</span><span>{{ $kuk['text'] }}</span></li>@endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
            @if ($module->assessment)
                <div class="fw-bolder mb-2">Penilaian 4 dimensi</div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-row-bordered mb-0 bg-white">
                        @foreach ($module->assessment as $a)
                            <tr><td class="fw-bold text-nowrap ps-3">{{ $a['label'] ?? $a['dimension'] }}</td><td class="text-nowrap">{{ $a['weight'] }}%</td>
                                <td>{{ $a['indicator'] ?? '' }}@if (! empty($a['evidence']))<div class="text-muted">Bukti/metode: {{ $a['evidence'] }}</div>@endif</td></tr>
                        @endforeach
                    </table>
                </div>
            @endif
            @foreach ($module->context ?? [] as $label => $text)
                @if ($text)<div class="mb-2"><b>{{ $label }}:</b> {{ $text }}</div>@endif
            @endforeach
        </div>
    </div>
@elseif ($module->summary)
    <p class="text-muted fs-7 mb-3">{{ $module->summary }}</p>
@endif
