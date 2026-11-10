<x-dashboard.page title="Area Piket" :crumbs="['Asrama', 'Jadwal Piket', 'Area']">
    <div class="row g-5">
        @foreach ($areas as $area)
            <div class="col-md-6 col-xl-4">
                <form method="POST" action="{{ route('duty-area.update', $area) }}" class="card h-100">
                    @csrf @method('PATCH')
                    <div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder">{{ $area->name }} @unless ($area->is_active)<span class="badge badge-light ms-2">nonaktif</span>@endunless</div>
                        <div class="card-toolbar text-muted fs-8">{{ $area->duties_count }} jadwal</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="name" :id="'area-name-' . $area->id" label="Nama" :value="$area->name" required />
                        <x-dashboard.field name="description" :id="'area-desc-' . $area->id" label="SOP singkat" type="textarea" :value="$area->description" />
                        <x-dashboard.field name="checklist" :id="'area-check-' . $area->id" label="Ceklis tugas (satu per baris)" type="textarea" rows="5" :value="implode(PHP_EOL, $area->checklist ?? [])" />
                        <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($area->is_active)> <span class="form-check-label">Aktif</span></label>
                    </div>
                    @can('dorm-update')<div class="card-footer d-flex justify-content-between">
                        @if (! $area->duties_count && auth()->user()->can('dorm-delete'))<button type="button" class="btn btn-sm btn-light-danger" data-delete-url="{{ route('duty-area.destroy', $area) }}">Hapus</button>@else<span></span>@endif
                        <button class="btn btn-sm btn-primary">Simpan</button></div>@endcan
                </form>
            </div>
        @endforeach
        @can('dorm-create')
            <div class="col-md-6 col-xl-4">
                <form method="POST" action="{{ route('duty-area.store') }}" class="card h-100">
                    @csrf
                    <div class="card-header min-h-50px"><div class="card-title fs-5 fw-bolder">Area baru</div></div>
                    <div class="card-body">
                        <x-dashboard.field name="name" label="Nama" required />
                        <x-dashboard.field name="description" label="SOP singkat" type="textarea" />
                        <x-dashboard.field name="checklist" label="Ceklis tugas (satu per baris)" type="textarea" rows="5" />
                        <input type="hidden" name="is_active" value="1">
                    </div>
                    <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Tambah</button></div>
                </form>
            </div>
        @endcan
    </div>
    <a href="{{ route('duty.index') }}" class="btn btn-sm btn-light mt-5"><i class="fa fa-arrow-left"></i> Jadwal piket</a>
</x-dashboard.page>
