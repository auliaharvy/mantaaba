{{-- Jabatan PD & PC pada periode aktif (PRD 8.4 AC) --}}
@php($groups = $user->activePositions())
@foreach (['pd' => 'Jabatan PD', 'pc' => 'Jabatan PC'] as $type => $label)
    <div class="fw-bolder mt-5">{{ $label }}</div>
    <div class="text-gray-600">
        @forelse ($groups->get($type, collect()) as $board)
            <div>{{ $board->position->name }}@if ($board->division) — {{ $board->division->name }}@endif
                <span class="text-muted">({{ $board->period->organization->name }})</span></div>
        @empty
            -
        @endforelse
    </div>
@endforeach
