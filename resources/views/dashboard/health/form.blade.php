@php($editing = $record->exists)
<x-dashboard.page :title="$editing ? 'Ubah Catatan Kesehatan' : 'Catat Kesehatan'" :crumbs="['Anggota', 'Kesehatan', $editing ? 'Ubah' : 'Catat']">
    <form method="POST" action="{{ $editing ? route('health.update', $record) : route('health.store') }}" class="card mw-800px">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card-body row">
            <div class="col-md-8"><x-dashboard.field name="member_id" label="Anggota" type="select" :options="$members" :value="$record->member_id" required /></div>
            <div class="col-md-4"><x-dashboard.field name="date" label="Tanggal" type="date" :value="$record->date" required /></div>
            <div class="col-12"><x-dashboard.field name="complaint" label="Keluhan" :value="$record->complaint" required /></div>
            <div class="col-md-4"><x-dashboard.field name="action" label="Tindakan" type="select" :options="App\Models\HealthRecord::ACTIONS" :value="$record->action" required /></div>
            <div class="col-md-4"><x-dashboard.field name="facility" label="Fasilitas kesehatan" :value="$record->facility" /></div>
            <div class="col-md-4"><x-dashboard.field name="cost" label="Biaya (Rp)" type="number" min="0" step="1" :value="$record->cost" help="Pencatatan ke voucher pengeluaran tersedia setelah modul keuangan." /></div>
            <div class="col-12"><x-dashboard.field name="note" label="Catatan" type="textarea" :value="$record->note" /></div>
        </div>
        <div class="card-footer text-end"><a href="{{ route('health.index') }}" class="btn btn-sm btn-light">Batal</a> <button class="btn btn-sm btn-primary">Simpan</button></div>
    </form>
</x-dashboard.page>
