@php
    $s = $summary;
    $rp = fn ($cents) => App\Support\Money::format(App\Support\Money::decimal($cents));
    $q = fn ($n) => App\Services\StockLedger::qty((float) $n);
    $user = auth()->user();
    $canLog = ! $cycle->isLocked() && $user->can('farm-log');
@endphp
<x-dashboard.page :title="$cycle->commodity . ' · ' . $cycle->plot->code" :crumbs="['Operasional', 'Kebun & Kolam', $cycle->label()]">
    <div class="card mb-5">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
                <span class="badge {{ ['berjalan' => 'badge-light-primary', 'panen' => 'badge-light-success', 'selesai' => 'badge-light-dark', 'gagal' => 'badge-light-danger'][$cycle->status] }}">{{ App\Models\FarmCycle::STATUSES[$cycle->status] }}</span>
                <span class="text-muted fs-7">{{ $cycle->plot->name }} · mulai {{ $cycle->started_at->translatedFormat('j M Y') }}{{ $cycle->expected_harvest_at ? ' · est. panen ' . $cycle->expected_harvest_at->translatedFormat('j M Y') : '' }}</span>
                @if ($cycle->failure_reason)<span class="text-danger fs-7">Gagal: {{ $cycle->failure_reason }}</span>@endif
                <a href="{{ route('farm.index') }}" class="btn btn-sm btn-light-primary ms-auto"><i class="fa fa-arrow-left"></i></a>
            </div>
            <div class="row g-3 text-center">
                @foreach ([['Biaya input', $rp($s['cost'])], ['Total panen', $q($s['harvest_qty']) . ' ' . ($cycle->harvests->first()?->unit ?? $cycle->target_unit)], ['HPP per satuan', $s['hpp'] !== null ? $rp($s['hpp']) : '-'],
                    ['Nilai hasil', $rp($s['value'])], ['Surplus', $rp($s['surplus'])], ['Capaian target', $s['target_percent'] !== null ? $s['target_percent'] . '%' : '-']] as [$label, $value])
                    <div class="col-6 col-md-2"><div class="border rounded py-3"><div class="text-muted fs-8">{{ $label }}</div><div class="fw-bolder">{{ $value }}</div></div></div>
                @endforeach
            </div>
            @if ($s['survival'] !== null)<p class="mt-3 mb-0 fs-7">Tingkat hidup <b>{{ $s['survival'] }}%</b> · populasi {{ $q($s['population']) }} dari {{ $q($cycle->initial_qty) }} {{ $cycle->initial_unit }}</p>@endif
            <p class="text-muted fs-8 mt-2 mb-0">Biaya input = stok yang dipakai {{ $rp($s['stock_cost']) }} + beban voucher bertanda siklus ini {{ $rp($s['journal_cost']) }}. Panen ke dapur bernilai Rp 0 di persediaan; nilai pasarnya hanya untuk laporan.</p>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card mb-5" id="log">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Log aktivitas</div></div>
                @if ($canLog)
                    <form method="POST" action="{{ route('farm.log', $cycle) }}" enctype="multipart/form-data" class="card-body border-bottom row g-2">
                        @csrf
                        <div class="col-6"><x-dashboard.field name="activity" label="Aktivitas" type="select" required :options="App\Models\FarmLog::ACTIVITIES" /></div>
                        <div class="col-6"><x-dashboard.field name="date" label="Tanggal" type="date" required :value="today()->toDateString()" /></div>
                        <div class="col-6"><x-dashboard.field name="qty" label="Jumlah" type="number" step="0.001" min="0" /></div>
                        <div class="col-6"><x-dashboard.field name="photo" label="Foto" type="file" accept="image/*" capture="environment" /></div>
                        <div class="col-md-6"><x-dashboard.field name="item_id" label="Barang dipakai (opsional)" type="select" :options="$items->mapWithKeys(fn ($i) => [$i->id => $i->label()])->all()" /></div>
                        <div class="col-md-6"><x-dashboard.field name="warehouse_id" label="Dari gudang" type="select" :value="App\Models\Warehouse::byCode('KBN')?->id" :options="$warehouses->pluck('name', 'id')->all()" /></div>
                        <div class="col-12"><x-dashboard.field name="description" label="Keterangan" /></div>
                        <div class="col-12 text-end"><button class="btn btn-sm btn-primary">Simpan log</button></div>
                    </form>
                @endif
                <div class="card-body p-0">
                    <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0 fs-7">
                        @forelse ($cycle->logs as $log)
                            <tr>
                                <td class="w-75px">{{ $log->date->translatedFormat('j M') }}</td>
                                <td><b>{{ App\Models\FarmLog::ACTIVITIES[$log->activity] }}</b>
                                    @if ($log->item){{ $log->item->name }} {{ $q($log->qty) }} {{ $log->unit }}@elseif ($log->qty) {{ $q($log->qty) }} {{ $log->unit }}@endif
                                    @if ($log->description)<div class="text-muted">{{ $log->description }}</div>@endif</td>
                                <td class="text-end">{{ $log->creator?->name }}@if ($log->photo) <a href="{{ Storage::disk('public')->url($log->photo) }}" target="_blank" title="Foto"><i class="fa fa-camera"></i></a>@endif</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-6">Belum ada log.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-5" id="panen">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Panen</div></div>
                @if ($canLog)
                    <form method="POST" action="{{ route('farm.harvest', $cycle) }}" class="card-body border-bottom row g-2">
                        @csrf
                        <div class="col-6"><x-dashboard.field name="date" label="Tanggal" type="date" required :value="today()->toDateString()" id="h-date" /></div>
                        <div class="col-6"><x-dashboard.field name="qty" label="Jumlah" type="number" step="0.001" min="0" required id="h-qty" /></div>
                        <div class="col-12"><x-dashboard.field name="item_id" id="h-item" label="Hasil panen" type="select" required :options="$harvestItems->mapWithKeys(fn ($i) => [$i->id => $i->label()])->all()" help="Barang berkategori hasil panen (tambah di Stok & Barang)." /></div>
                        <div class="col-6"><x-dashboard.field name="destination" label="Tujuan" type="select" required value="dapur" :options="App\Models\Harvest::DESTINATIONS" /></div>
                        <div class="col-6"><x-dashboard.field name="warehouse_id" label="Gudang" type="select" required :value="App\Models\Warehouse::byCode('DPR')?->id" :options="$warehouses->pluck('name', 'id')->all()" id="h-warehouse" /></div>
                        <div class="col-6"><x-dashboard.field name="market_price" label="Harga pasar /satuan" type="number" step="1" min="0" required /></div>
                        <div class="col-6"><x-dashboard.field name="grade" label="Grade" /></div>
                        <div class="col-6"><x-dashboard.field name="sale_price" label="Harga jual /satuan" type="number" step="1" min="0" help="Bila dijual." /></div>
                        <div class="col-6"><x-dashboard.field name="payment" label="Cara bayar" type="select" :options="App\Models\Harvest::PAYMENTS" /></div>
                        <div class="col-12"><x-dashboard.field name="party_id" label="Pembeli" type="select" :options="$parties->pluck('name', 'id')->all()" id="h-party" /></div>
                        <div class="col-12 text-end"><button class="btn btn-sm btn-success">Simpan panen</button></div>
                    </form>
                @endif
                <div class="card-body p-0">
                    <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0 fs-7">
                        @forelse ($cycle->harvests as $h)
                            <tr>
                                <td class="w-75px">{{ $h->date->translatedFormat('j M') }}</td>
                                <td>{{ $q($h->qty) }} {{ $h->unit }} {{ $h->item->name }} → <b>{{ App\Models\Harvest::DESTINATIONS[$h->destination] }}</b>
                                    <div class="text-muted">nilai {{ $rp($h->valueCents()) }}@if ($h->journal) · <a href="{{ route('journal.show', $h->journal) }}">{{ $h->journal->displayNumber() }}</a>@endif</div></td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-6">Belum ada panen.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
            @if (! $cycle->isLocked() && $user->can('farm-update'))
                <form method="POST" action="{{ route('farm.close', $cycle) }}" class="card" onsubmit="return confirm('Tutup siklus? Setelah ditutup, log & panen terkunci.')">
                    @csrf
                    <div class="card-body">
                        <x-dashboard.field name="status" label="Tutup siklus sebagai" type="select" required value="selesai" :options="['selesai' => 'Selesai', 'gagal' => 'Gagal']" />
                        <x-dashboard.field name="failure_reason" label="Penyebab (bila gagal)" />
                        <button class="btn btn-sm btn-light-dark w-100">Tutup siklus</button>
                    </div>
                </form>
            @elseif ($cycle->isLocked() && $user->can('farm-update'))
                <form method="POST" action="{{ route('farm.reopen', $cycle) }}" class="card" onsubmit="const r = prompt('Alasan membuka ulang siklus:'); if (!r) return false; this.reason.value = r;">
                    @csrf <input type="hidden" name="reason">
                    <div class="card-body"><button class="btn btn-sm btn-light-warning w-100">Buka ulang siklus</button><p class="text-muted fs-8 mt-2 mb-0">Tercatat di log aktivitas.</p></div>
                </form>
            @endif
        </div>
    </div>
</x-dashboard.page>
