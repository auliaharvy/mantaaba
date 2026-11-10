{{-- Catatan pembinaan (PRD 9.5): input cepat dari HP, bisa untuk banyak siswa sekaligus. --}}
<x-dashboard.page title="Catatan Pembinaan" :crumbs="['Anggota', 'Catatan Pembinaan']">
    @can('coaching-create')
        <form method="POST" action="{{ route('coaching.store') }}" class="card mb-5">
            @csrf
            <div class="card-header"><div class="card-title fs-4 fw-bolder">Catat cepat</div></div>
            <div class="card-body">
                <label class="form-label required">Siswa <span class="text-muted fs-8">(boleh lebih dari satu untuk catatan kelompok)</span></label>
                <div class="d-flex flex-wrap gap-2 mb-6">
                    @forelse ($members as $m)
                        <input type="checkbox" class="btn-check" name="member_ids[]" value="{{ $m->id }}" id="m{{ $m->id }}" autocomplete="off" @checked(in_array($m->id, old('member_ids', $selected)))>
                        <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-light-primary" for="m{{ $m->id }}">{{ $m->name }}</label>
                    @empty
                        <span class="text-muted">Belum ada siswa aktif.</span>
                    @endforelse
                </div>
                @error('member_ids')<div class="text-danger fs-7 mb-4">{{ $message }}</div>@enderror
                <div class="row">
                    <div class="col-md-3"><x-dashboard.field name="date" label="Tanggal" type="date" :value="today()" required /></div>
                    <div class="col-md-3"><x-dashboard.field name="type" label="Jenis" type="select" :options="App\Models\CoachingNote::TYPES" value="positif" required /></div>
                    <div class="col-md-3"><x-dashboard.field name="dimension" label="Dimensi" type="select" :options="App\Models\CoachingNote::DIMENSIONS" required /></div>
                    <div class="col-md-3"><x-dashboard.field name="visibility" label="Terlihat oleh" type="select" :options="App\Models\CoachingNote::VISIBILITIES" value="privat" required /></div>
                    <div class="col-md-8"><x-dashboard.field name="body" label="Catatan" type="textarea" required /></div>
                    <div class="col-md-4"><x-dashboard.field name="follow_up" label="Tindak lanjut" type="textarea" /></div>
                </div>
            </div>
            <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Simpan catatan</button></div>
        </form>
    @endcan
    <div class="card">
        <div class="card-header"><div class="card-title fs-4 fw-bolder">Riwayat catatan</div></div>
        <div class="card-body p-lg-8">
            <x-dashboard.datatable :url="route('coaching.datatable')" :order="[[1, 'desc']]" :columns="[
                ['data' => 'DT_RowIndex', 'title' => 'No.'],
                ['data' => 'date', 'title' => 'Tanggal', 'searchable' => false],
                ['data' => 'member.name', 'name' => 'member.name', 'title' => 'Anggota', 'orderable' => false],
                ['data' => 'type', 'title' => 'Jenis', 'searchable' => false],
                ['data' => 'dimension', 'title' => 'Dimensi', 'searchable' => false],
                ['data' => 'body', 'title' => 'Catatan'],
                ['data' => 'author_name', 'title' => 'Oleh', 'orderable' => false, 'searchable' => false],
                ['data' => 'action', 'title' => ''],
            ]" />
        </div>
    </div>
</x-dashboard.page>
