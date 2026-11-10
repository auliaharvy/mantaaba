@php($editing = $room->exists)
<x-dashboard.page :title="$editing ? 'Ubah Kamar' : 'Tambah Kamar'" :crumbs="['Asrama', 'Kamar', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('room.update', $room) : route('room.store') }}" class="card mw-700px">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-body row">
            <div class="col-md-3"><x-dashboard.field name="code" label="Kode" :value="$room->code" required placeholder="B-2" /></div>
            <div class="col-md-5"><x-dashboard.field name="name" label="Nama" :value="$room->name" required placeholder="Kamar Al-Fatih" /></div>
            <div class="col-md-4"><x-dashboard.field name="building" label="Gedung" :value="$room->building" /></div>
            <div class="col-md-4"><x-dashboard.field name="capacity" label="Kapasitas" type="number" min="1" :value="$room->capacity" required /></div>
            <div class="col-md-4"><x-dashboard.field name="gender" label="Peruntukan" type="select" :options="App\Models\Room::GENDERS" :value="$room->gender" required /></div>
            <div class="col-12"><x-dashboard.field name="notes" label="Catatan" type="textarea" :value="$room->notes" /></div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            @if ($editing && auth()->user()->can('dorm-delete'))<button type="button" class="btn btn-sm btn-light-danger" data-delete-url="{{ route('room.destroy', $room) }}">Hapus kamar</button>@else<span></span>@endif
            <div><a href="{{ route('room.index') }}" class="btn btn-sm btn-light">Batal</a> <button class="btn btn-sm btn-primary">Simpan</button></div>
        </div>
    </form>
</x-dashboard.page>
