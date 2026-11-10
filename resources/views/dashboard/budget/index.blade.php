<x-dashboard.page title="Anggaran" :crumbs="['Keuangan', 'Anggaran']">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <form method="GET" class="d-flex gap-2 align-items-center"><label for="tahun" class="fw-bold">Tahun</label>
                <input id="tahun" type="number" name="tahun" value="{{ $year }}" class="form-control form-control-sm w-100px" onchange="this.form.submit()"></form>
            <a href="{{ route('report.show', ['anggaran', 'year' => $year]) }}" class="btn btn-sm btn-light-primary ms-auto"><i class="fa fa-chart-bar"></i> Laporan realisasi</a>
        </div>
    </div>
    @can('coa-create')
        <form method="POST" action="{{ route('budget.store') }}" class="card mb-5">
            @csrf <input type="hidden" name="year" value="{{ $year }}">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-5"><x-dashboard.field name="coa_account_id" label="Akun" type="select" required :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->label()])->all()" /></div>
                <div class="col-md-3"><x-dashboard.field name="cost_center_id" label="Pusat biaya" type="select" required :options="$centers->mapWithKeys(fn ($c) => [$c->id => $c->code . ' · ' . $c->name])->all()" /></div>
                <div class="col-md-2"><x-dashboard.field name="amount" label="Anggaran setahun (Rp)" type="number" required min="0" step="1000" /></div>
                <div class="col-md-2 mb-6"><button class="btn btn-primary w-100">Simpan</button></div>
            </div>
        </form>
    @endcan
    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0">
                <thead><tr class="fw-bold bg-light fs-7"><th>Akun</th><th>Pusat biaya</th><th class="text-end">Anggaran</th><th class="text-end">Realisasi</th><th class="text-end">Serapan</th><th></th></tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td>{{ $r['budget']->account->label() }}</td>
                            <td>{{ $r['budget']->costCenter->code }}</td>
                            <td class="text-end">{{ App\Support\Money::format(App\Support\Money::decimal($r['planned'])) }}</td>
                            <td class="text-end">{{ App\Support\Money::format(App\Support\Money::decimal($r['actual'])) }}</td>
                            <td class="text-end"><span class="badge {{ ($r['percent'] ?? 0) > 100 ? 'badge-light-danger' : (($r['percent'] ?? 0) > 80 ? 'badge-light-warning' : 'badge-light-success') }}">{{ $r['percent'] !== null ? $r['percent'] . '%' : '-' }}</span></td>
                            <td class="text-end">@can('coa-delete')<button type="button" class="btn btn-sm btn-icon btn-light-danger" title="Hapus" data-delete-url="{{ route('budget.destroy', $r['budget']) }}"><i class="fa fa-trash"></i></button>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-10">Belum ada anggaran {{ $year }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-muted fs-7 mt-4">Voucher beban yang membuat realisasi melebihi anggaran memunculkan peringatan saat diajukan (tetap bisa dilanjutkan dengan konfirmasi).</p>
</x-dashboard.page>
