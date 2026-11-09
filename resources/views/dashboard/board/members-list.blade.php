{{-- Pengurus satu periode, terurut divisi -> jabatan -> anggota (PRD 8.4). $manage = tampilkan tombol hapus. --}}
@php($manage = $manage ?? false)
@forelse ($period->boardMembers->groupBy(fn ($b) => $b->division?->name ?? 'Pengurus Inti') as $group => $rows)
    <div class="fw-bolder text-gray-800 mt-4 mb-2">{{ $group }}</div>
    @foreach ($rows as $board)
        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
            <div>
                <span class="fw-bold">{{ $board->position->name }}</span>
                <span class="text-gray-600">— {{ $board->user->name }}</span>
            </div>
            @if ($manage)
                <form method="POST" action="{{ route('board-member.destroy', $board) }}" onsubmit="return confirm('Hapus {{ e($board->user->name) }} dari jabatan ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-icon btn-light-danger" title="Hapus"><i class="fa fa-trash"></i></button>
                </form>
            @endif
        </div>
    @endforeach
@empty
    <div class="text-muted">Belum ada pengurus pada periode ini.</div>
@endforelse
