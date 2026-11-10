{{-- Denah kamar (PRD 9.9): kartu per kamar, penghuni, sisa kapasitas. --}}
@php($canUpdate = auth()->user()->can('dorm-update'))
<x-dashboard.page title="Kamar Asrama" :crumbs="['Asrama', 'Kamar']">
    @error('member_id')<div class="alert alert-danger">{{ $message }}</div>@enderror
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
        <div class="text-muted">{{ $rooms->sum(fn ($r) => $r->residents->count()) }} penghuni · {{ $rooms->sum('capacity') }} tempat tidur · {{ $unassigned->count() }} anggota aktif belum berkamar</div>
        @can('dorm-create')<a href="{{ route('room.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Tambah Kamar</a>@endcan
    </div>
    <div class="row g-5">
        @forelse ($rooms as $room)
            @php($left = $room->capacity - $room->residents->count())
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border {{ $left === 0 ? 'border-warning' : '' }}">
                    <div class="card-header min-h-60px">
                        <div class="card-title flex-column align-items-start"><span class="fw-bolder fs-4">{{ $room->code }} · {{ $room->name }}</span>
                            <span class="text-muted fs-7">{{ $room->building ?: '-' }} · {{ App\Models\Room::GENDERS[$room->gender] }}</span></div>
                        <div class="card-toolbar gap-1">
                            <span class="badge {{ $left === 0 ? 'badge-light-warning' : 'badge-light-success' }}">{{ $room->residents->count() }}/{{ $room->capacity }}</span>
                            @if ($canUpdate)<a href="{{ route('room.edit', $room) }}" class="btn btn-sm btn-icon btn-light" title="Ubah kamar"><i class="fa fa-pen"></i></a>@endif
                        </div>
                    </div>
                    <div class="card-body py-3">
                        @forelse ($room->residents as $resident)
                            <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                                <a href="{{ route('member.show', $resident) }}" class="flex-grow-1">{{ $resident->name }}</a>
                                @if ($canUpdate)
                                    <form method="POST" action="{{ route('room.unassign', $resident) }}" onsubmit="return confirm('Keluarkan {{ e($resident->name) }} dari kamar?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-light-danger" title="Keluarkan"><i class="fa fa-times"></i></button></form>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted fs-7 my-2">Kosong.</p>
                        @endforelse
                    </div>
                    @if ($canUpdate && $left > 0)
                        @php($candidates = $unassigned->where('gender', $room->gender))
                        <form method="POST" action="{{ route('room.assign', $room) }}" class="card-footer d-flex gap-2 py-3">
                            @csrf
                            <select name="member_id" class="form-select form-select-sm" required aria-label="Penghuni baru {{ $room->code }}">
                                <option value="">+ Penghuni (sisa {{ $left }})</option>
                                @foreach ($candidates as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                            </select>
                            <button class="btn btn-sm btn-light-primary">Tempatkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-muted">Belum ada kamar.</div></div></div>
        @endforelse
    </div>
    @if ($unassigned->whereNull('gender')->isNotEmpty())
        <p class="text-muted fs-7 mt-5">Anggota tanpa data jenis kelamin tidak bisa ditempatkan: {{ $unassigned->whereNull('gender')->pluck('name')->implode(', ') }}.</p>
    @endif
</x-dashboard.page>
