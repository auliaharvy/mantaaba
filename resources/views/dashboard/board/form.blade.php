@php($editing = $period->exists)
<x-dashboard.page :title="$editing ? 'Ubah Periode' : 'Tambah Periode'" :crumbs="['Organisasi', 'Kepengurusan', $editing ? 'Ubah' : 'Tambah']">
    <form method="POST" action="{{ $editing ? route('board.update', $period) : route('board.store') }}" enctype="multipart/form-data" autocomplete="off">
        @csrf
        @if ($editing) @method('PATCH') @endif
        <div class="card">
            <div class="card-header">
                <div class="card-title fs-3 fw-bolder">Periode Kepengurusan</div>
                <div class="card-toolbar">
                    <a class="btn btn-sm btn-light-primary" href="{{ $editing ? route('board.show', $period) : route('board.index') }}"><i class="fa fa-arrow-left"></i> Kembali</a>
                </div>
            </div>
            <div class="card-body row">
                <div class="col-md-6"><x-dashboard.field name="organization_id" label="Organisasi (PD/PC)" type="select" :options="$organizations" :value="$period->organization_id" required /></div>
                <div class="col-md-6"><x-dashboard.field name="name" label="Nama periode" :value="$period->name" placeholder="2024–2029" required /></div>
                <div class="col-md-3"><x-dashboard.field name="starts_at" label="Mulai" type="date" :value="$period->starts_at" /></div>
                <div class="col-md-3"><x-dashboard.field name="ends_at" label="Selesai" type="date" :value="$period->ends_at" /></div>
                <div class="col-md-6"><x-dashboard.field name="sk_number" label="Nomor SK" :value="$period->sk_number" /></div>
                <div class="col-md-6">
                    <x-dashboard.field name="sk_file" label="File SK (PDF, maks 10 MB)" type="file" accept="application/pdf" />
                    @if ($period->sk_file)
                        <a href="{{ Storage::disk('public')->url($period->sk_file) }}" target="_blank" class="fs-7">Lihat SK saat ini</a>
                    @endif
                </div>
                <div class="col-12 form-check form-switch ms-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $period->is_active))>
                    <label class="form-check-label" for="is_active">Periode aktif (periode aktif lain di organisasi ini otomatis dinonaktifkan)</label>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
            </div>
        </div>
    </form>
</x-dashboard.page>
