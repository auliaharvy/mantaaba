<x-dashboard.page title="Pusat Biaya" :crumbs="['Keuangan', 'COA & Master', 'Pusat Biaya']">
    @include('dashboard.coa.tabs', ['active' => 'center'])
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card mb-5">
        <div class="card-body p-0">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0">
                <thead><tr class="fw-bold bg-light fs-7"><th>Kode</th><th>Nama</th><th>Jenis</th><th>Aktif</th><th class="text-end">Baris jurnal</th><th></th></tr></thead>
                <tbody>
                    @foreach ($centers as $c)
                        @php $f = 'cc-' . $c->id; $ro = auth()->user()->cannot('coa-update'); @endphp
                        <tr>
                            <td><input form="{{ $f }}" name="code" value="{{ $c->code }}" class="form-control form-control-sm w-100px" aria-label="Kode" @disabled($ro)></td>
                            <td><input form="{{ $f }}" name="name" value="{{ $c->name }}" class="form-control form-control-sm" aria-label="Nama" @disabled($ro)>@if ($c->businessUnit)<div class="fs-8 text-muted">unit bisnis: {{ $c->businessUnit->name }}</div>@endif</td>
                            <td><select form="{{ $f }}" name="type" class="form-select form-select-sm" aria-label="Jenis" @disabled($ro)>@foreach (App\Models\CostCenter::TYPES as $v => $l)<option value="{{ $v }}" @selected($c->type === $v)>{{ $l }}</option>@endforeach</select></td>
                            <td><input form="{{ $f }}" type="checkbox" class="form-check-input" name="is_active" value="1" @checked($c->is_active) aria-label="Aktif" @disabled($ro)></td>
                            <td class="text-end">{{ $c->lines_count ?: '' }}</td>
                            <td class="text-end">@unless ($ro)<form id="{{ $f }}" method="POST" action="{{ route('cost-center.update', $c) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light-primary">Simpan</button></form>@endunless</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @can('coa-create')
        <form method="POST" action="{{ route('cost-center.store') }}" class="card mw-800px">
            @csrf
            <div class="card-header"><div class="card-title fs-5 fw-bolder">Tambah pusat biaya</div></div>
            <div class="card-body row">
                <div class="col-md-3"><x-dashboard.field name="code" label="Kode" required placeholder="TJG" /></div>
                <div class="col-md-5"><x-dashboard.field name="name" label="Nama" required /></div>
                <div class="col-md-4"><x-dashboard.field name="type" label="Jenis" type="select" :options="App\Models\CostCenter::TYPES" required /></div>
                <input type="hidden" name="is_active" value="1">
            </div>
            <div class="card-footer text-end"><button class="btn btn-sm btn-primary">Tambah</button></div>
        </form>
    @endcan
    <p class="text-muted fs-7 mt-4">Unit bisnis baru otomatis mendapat pusat biaya sendiri.</p>
</x-dashboard.page>
