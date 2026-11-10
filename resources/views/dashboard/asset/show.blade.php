@php
    $rp = fn ($cents) => App\Support\Money::format(App\Support\Money::decimal($cents));
    $v = $asset->vehicle;
    $user = auth()->user();
    $active = ! $asset->disposed_at;
    $next = $v?->nextService();
    $due = fn ($date) => $date ? $date->translatedFormat('j M Y') . ' (' . (int) today()->diffInDays($date, false) . ' hari)' : '-';
@endphp
<x-dashboard.page :title="$asset->code . ' · ' . $asset->name" :crumbs="['Operasional', 'Aset', $asset->code]">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap gap-5">
            @if ($asset->photo)<img src="{{ Storage::disk('public')->url($asset->photo) }}" alt="Foto {{ $asset->name }}" class="rounded w-150px h-100px object-fit-cover">@endif
            <div class="flex-grow-1">
                <div class="fs-3 fw-bolder">{{ $asset->name }}@if ($v?->plate_number) · {{ $v->plate_number }}@endif</div>
                <div class="text-muted">{{ $asset->categoryLabel() }} · Kondisi {{ App\Models\Asset::CONDITIONS[$asset->condition] }} · PIC {{ $asset->pic?->name ?? '-' }} · {{ $asset->location }}</div>
                <div class="mt-2">Nilai buku <b>{{ $rp($asset->bookValueCents()) }}</b> (perolehan {{ App\Support\Money::format($asset->acquisition_value) }}{{ $asset->accumulated_account_id ? ', akum. penyusutan ' . $rp($accumulated) : '' }})
                    @if ($asset->journal) · <a href="{{ route('journal.show', $asset->journal) }}">{{ $asset->journal->displayNumber() }}</a>@endif</div>
                @if ($asset->disposed_at)<div class="text-danger mt-1">Dilepas {{ $asset->disposed_at->translatedFormat('j M Y') }}: {{ $asset->disposal_note }}@if ($asset->disposalJournal) · <a href="{{ route('journal.show', $asset->disposalJournal) }}">{{ $asset->disposalJournal->displayNumber() }}</a>@endif</div>@endif
                @if ($v)
                    <div class="mt-2 fs-7">Odometer <b>{{ number_format($v->last_odometer, 0, ',', '.') }} km</b>
                        @if ($next['km']) · servis berikutnya {{ number_format($next['km'], 0, ',', '.') }} km (<span class="{{ $next['km'] - $v->last_odometer <= 500 ? 'text-danger fw-bold' : '' }}">{{ number_format($next['km'] - $v->last_odometer, 0, ',', '.') }} km lagi</span>)@endif
                        @if ($next['date']) · {{ $due($next['date']) }}@endif</div>
                    <div class="fs-7">Pajak {{ $due($v->tax_due_at) }} · STNK {{ $due($v->stnk_due_at) }}@if ($v->kir_due_at) · KIR {{ $due($v->kir_due_at) }}@endif</div>
                @endif
                @if ($asset->documents)<div class="mt-2 fs-7">Dokumen: @foreach ($asset->documents as $i => $doc)<a href="{{ route('asset.document', [$asset, $i]) }}" target="_blank">{{ $doc['name'] }}</a>@unless ($loop->last), @endunless @endforeach</div>@endif
            </div>
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('asset.labels', ['ids' => [$asset->id]]) }}" class="btn btn-sm btn-light"><i class="fa fa-qrcode"></i> Label QR</a>
                @can('asset-update')<a href="{{ route('asset.edit', $asset) }}" class="btn btn-sm btn-light-success"><i class="fa fa-pen"></i> Ubah</a>@endcan
                <a href="{{ route('asset.index') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i></a>
            </div>
        </div>
    </div>

    @if ($summary)
        <div class="row g-3 mb-5 text-center">
            @foreach ([['Total km', number_format($summary['km'], 0, ',', '.')], ['BBM', $rp($summary['fuel']) . ' · ' . App\Services\StockLedger::qty($summary['liters']) . ' L'], ['km/liter', $summary['km_per_liter'] ?? '-'],
                ['Biaya per km', $summary['cost_per_km'] !== null ? $rp($summary['cost_per_km']) : '-'], ['Perawatan ' . now()->year, $rp($summary['maintenance_year'])]] as [$label, $value])
                <div class="col-6 col-md"><div class="card"><div class="card-body py-4"><div class="text-muted fs-8">{{ $label }}</div><div class="fw-bolder">{{ $value }}</div></div></div></div>
            @endforeach
        </div>
    @endif

    <div class="row g-5">
        <div class="col-lg-7">
            @if ($asset->isVehicle())
                <div class="card mb-5">
                    <div class="card-header"><div class="card-title fs-5 fw-bolder">Pemakaian</div></div>
                    @if ($active && $user->can('asset-usage'))
                        <div class="card-body border-bottom">
                            @if ($openUsage)
                                <p class="mb-3"><b>Sedang dipakai</b> {{ $openUsage->user?->name }} sejak {{ $openUsage->started_at->translatedFormat('j M H:i') }} · {{ $openUsage->purpose }} → {{ $openUsage->destination }} (km awal {{ $openUsage->odometer_start }})</p>
                                @if ($user->id === $openUsage->user_id || $user->can('asset-update'))
                                    <form method="POST" action="{{ route('asset.usage-end', $openUsage) }}" enctype="multipart/form-data" class="row g-2">
                                        @csrf
                                        <div class="col-6 col-md-3"><x-dashboard.field name="odometer_end" label="Km akhir" type="number" min="0" required /></div>
                                        <div class="col-6 col-md-3"><x-dashboard.field name="fuel_cost" label="BBM (Rp)" type="number" step="1" min="0" /></div>
                                        <div class="col-6 col-md-2"><x-dashboard.field name="fuel_liters" label="Liter" type="number" step="0.01" min="0" /></div>
                                        <div class="col-6 col-md-4"><x-dashboard.field name="receipt" label="Foto struk" type="file" accept="image/*,application/pdf" capture="environment" /></div>
                                        <div class="col-12 text-end"><button class="btn btn-sm btn-success">Selesai pakai</button></div>
                                    </form>
                                @endif
                            @else
                                <form method="POST" action="{{ route('asset.usage-start', $asset) }}" class="row g-2">
                                    @csrf
                                    <div class="col-md-4"><x-dashboard.field name="purpose" label="Keperluan" required /></div>
                                    <div class="col-md-4"><x-dashboard.field name="destination" label="Tujuan" /></div>
                                    <div class="col-md-4"><x-dashboard.field name="odometer_start" label="Km awal" type="number" min="0" :value="$v?->last_odometer" /></div>
                                    <div class="col-12 text-end"><button class="btn btn-sm btn-primary">Mulai pakai</button></div>
                                </form>
                            @endif
                        </div>
                    @endif
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0 fs-7">
                            @forelse ($asset->usages->take(20) as $u)
                                <tr><td>{{ $u->started_at->translatedFormat('j M') }}</td><td>{{ $u->user?->name }}<div class="text-muted">{{ $u->purpose }} {{ $u->destination ? '→ ' . $u->destination : '' }}</div></td>
                                    <td class="text-end">{{ $u->distance() !== null ? $u->distance() . ' km' : 'dipakai' }}</td>
                                    <td class="text-end">{{ $u->fuel_cost ? App\Support\Money::format($u->fuel_cost) : '' }}@if ($u->journal)<div><a href="{{ route('journal.show', $u->journal) }}">{{ $u->journal->displayNumber() }}</a></div>@endif</td></tr>
                            @empty
                                <tr><td class="text-center text-muted py-6">Belum ada pemakaian.</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            @endif
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Perawatan</div></div>
                @if ($active && $user->can('asset-maintenance'))
                    <form method="POST" action="{{ route('asset.maintenance', $asset) }}" enctype="multipart/form-data" class="card-body border-bottom row g-2">
                        @csrf
                        <div class="col-6 col-md-3"><x-dashboard.field name="date" label="Tanggal" type="date" required :value="today()->toDateString()" /></div>
                        <div class="col-6 col-md-3"><x-dashboard.field name="type" label="Jenis" type="select" required :options="App\Models\AssetMaintenance::TYPES" /></div>
                        <div class="col-md-6"><x-dashboard.field name="description" label="Uraian" required /></div>
                        <div class="col-6 col-md-3"><x-dashboard.field name="cost" label="Biaya (Rp)" type="number" step="1" min="0" /></div>
                        <div class="col-6 col-md-3"><x-dashboard.field name="odometer" label="Km" type="number" min="0" /></div>
                        <div class="col-6 col-md-3"><x-dashboard.field name="next_due_at" label="Jatuh tempo berikutnya" type="date" /></div>
                        <div class="col-6 col-md-3"><x-dashboard.field name="vendor_party_id" label="Bengkel/vendor" type="select" :options="$parties->pluck('name', 'id')->all()" /></div>
                        <div class="col-md-6"><x-dashboard.field name="attachment" label="Bukti" type="file" accept="image/*,application/pdf" /></div>
                        <div class="col-md-6 text-end align-self-end mb-6"><button class="btn btn-sm btn-primary">Simpan perawatan</button></div>
                    </form>
                @endif
                <div class="card-body p-0 table-responsive">
                    <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0 fs-7">
                        @forelse ($asset->maintenances as $m)
                            <tr><td>{{ $m->date->translatedFormat('j M Y') }}</td><td><b>{{ App\Models\AssetMaintenance::TYPES[$m->type] }}</b> {{ $m->description }}<div class="text-muted">{{ $m->vendor?->name }}{{ $m->odometer ? ' · ' . $m->odometer . ' km' : '' }}{{ $m->next_due_at ? ' · berikutnya ' . $m->next_due_at->translatedFormat('j M Y') : '' }}</div></td>
                                <td class="text-end">{{ App\Support\Money::format($m->cost) }}@if ($m->journal)<div><a href="{{ route('journal.show', $m->journal) }}">{{ $m->journal->displayNumber() }}</a></div>@endif</td></tr>
                        @empty
                            <tr><td class="text-center text-muted py-6">Belum ada perawatan.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">Jadwal penyusutan</div></div>
                <div class="card-body p-0">
                    @if ($schedule)
                        <p class="px-5 pt-4 fs-7 text-muted mb-2">Garis lurus {{ $asset->useful_life_months }} bulan mulai {{ $asset->depreciation_start->translatedFormat('M Y') }}; residu {{ App\Support\Money::format($asset->residual_value) }}. Dijurnal sekali sebulan saat tutup periode.</p>
                        <div class="mh-400px overflow-auto">
                            <table class="table table-row-bordered gs-5 gy-1 mb-0 fs-8">
                                <thead><tr class="fw-bold bg-light"><th>Bulan</th><th class="text-end">Beban</th><th class="text-end">Akumulasi</th></tr></thead>
                                @foreach ($schedule as $row)
                                    <tr class="{{ $row['month']->isSameMonth(today()) ? 'fw-bold bg-light-primary' : '' }}"><td>{{ $row['month']->translatedFormat('M Y') }}</td><td class="text-end">{{ $rp($row['amount']) }}</td><td class="text-end">{{ $rp($row['cumulative']) }}</td></tr>
                                @endforeach
                            </table>
                        </div>
                    @else
                        <p class="p-5 text-muted mb-0">{{ $asset->category === 'tanah' ? 'Tanah tidak disusutkan.' : 'Tidak disusutkan.' }}</p>
                    @endif
                </div>
            </div>
            @if ($active && $user->can('asset-update'))
                <form method="POST" action="{{ route('asset.dispose', $asset) }}" class="card" onsubmit="return confirm('Lepas aset ini? Voucher pelepasan dibuat untuk disetujui Bendahara.')">
                    @csrf
                    <div class="card-header"><div class="card-title fs-6 fw-bolder">Pelepasan (jual/hapus)</div></div>
                    <div class="card-body row g-2">
                        <div class="col-6"><x-dashboard.field name="disposed_at" label="Tanggal" type="date" required :value="today()->toDateString()" /></div>
                        <div class="col-6"><x-dashboard.field name="proceeds" label="Hasil jual (Rp)" type="number" step="1" min="0" /></div>
                        <div class="col-12"><x-dashboard.field name="coa_account_id" label="Diterima di" type="select" :options="$cashAccounts->mapWithKeys(fn ($a) => [$a->id => $a->label()])->all()" /></div>
                        <div class="col-12"><x-dashboard.field name="disposal_note" label="Alasan" required /></div>
                        <div class="col-12"><button class="btn btn-sm btn-light-danger w-100">Lepas aset</button></div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-dashboard.page>
