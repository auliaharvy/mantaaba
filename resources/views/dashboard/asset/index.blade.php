<x-dashboard.page title="Aset & Kendaraan" :crumbs="['Operasional', 'Aset']">
    <div class="card">
        <div class="card-header flex-wrap py-3 gap-2">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-200px" placeholder="Cari kode/nama" aria-label="Cari aset">
                <select name="kategori" class="form-select form-select-sm w-auto" onchange="this.form.submit()" aria-label="Kategori"><option value="">Semua kategori</option>@foreach (config('mantaaba.asset_categories') as $k => $c)<option value="{{ $k }}" @selected(request('kategori') === $k)>{{ $c[0] }}</option>@endforeach</select>
                <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()" aria-label="Status"><option value="">Aktif</option><option value="dilepas" @selected(request('status') === 'dilepas')>Dilepas</option></select>
            </form>
            <div class="card-toolbar gap-2">
                <button type="submit" form="label-form" class="btn btn-sm btn-light-primary"><i class="fa fa-qrcode"></i> Cetak label terpilih</button>
                @can('asset-create')<a href="{{ route('asset.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Aset</a>@endcan
            </div>
        </div>
        <form id="label-form" method="GET" action="{{ route('asset.labels') }}" class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0 fs-7">
                <thead><tr class="fw-bold bg-light"><th class="w-25px"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.asset-pick').forEach(c => c.checked = this.checked)" aria-label="Pilih semua"></th>
                    <th>Kode</th><th>Aset</th><th>Kategori</th><th>Kondisi</th><th>PIC</th><th class="text-end">Nilai buku</th></tr></thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $asset->id }}" class="form-check-input asset-pick" aria-label="Pilih {{ $asset->code }}"></td>
                            <td>{{ $asset->code }}</td>
                            <td><a href="{{ route('asset.show', $asset) }}" class="fw-bold">{{ $asset->name }}</a>@if ($asset->vehicle?->plate_number)<span class="text-muted"> · {{ $asset->vehicle->plate_number }}</span>@endif
                                @if (isset($inUse[$asset->id]))<span class="badge badge-light-warning ms-1">dipakai</span>@endif</td>
                            <td>{{ $asset->categoryLabel() }}</td>
                            <td>{{ App\Models\Asset::CONDITIONS[$asset->condition] }}</td>
                            <td>{{ $asset->pic?->name ?? '-' }}</td>
                            <td class="text-end">{{ App\Support\Money::format(App\Support\Money::decimal($asset->bookValueCents())) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-10">Belum ada aset.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>
</x-dashboard.page>
