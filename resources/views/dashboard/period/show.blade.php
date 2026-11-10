<x-dashboard.page :title="'Tutup Bulan ' . $period->label()" :crumbs="['Keuangan', 'Periode', $period->label()]">
    @php
        $canClose = auth()->user()->can('period-close') && ! $period->isClosed();
    @endphp
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <div class="row g-5">
        <div class="col-lg-6">
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">1. Voucher belum diposting</div></div>
                <div class="card-body">
                    @if ($check['pending'])
                        <p class="text-warning fw-bold mb-2"><i class="fa fa-exclamation-triangle text-warning"></i> {{ $check['pending'] }} voucher masih draf/diajukan.</p>
                        <a href="{{ route('journal.index') }}" class="btn btn-sm btn-light-primary">Lihat voucher</a>
                    @else
                        <p class="text-success mb-0"><i class="fa fa-check text-success"></i> Semua voucher periode ini sudah diproses.</p>
                    @endif
                </div>
            </div>
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">2. Jurnal otomatis bulanan</div>
                    @if ($canClose && $check['generators']->isNotEmpty())<div class="card-toolbar"><form method="POST" action="{{ route('period.generate', $period) }}">@csrf<button class="btn btn-sm btn-light-primary">Buat jurnal otomatis</button></form></div>@endif
                </div>
                <div class="card-body">
                    @forelse ($check['generators'] as $g)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>{{ $g['label'] }}</span>
                            @if ($g['journal'])<a href="{{ route('journal.show', $g['journal']) }}">{{ $g['journal']->number ?? 'draf' }} · {{ $g['journal']->status }}</a>@else<span class="text-muted">belum dibuat</span>@endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada jurnal otomatis terdaftar (pemakaian persediaan, penyusutan aset ditambahkan bersama modulnya).</p>
                    @endforelse
                </div>
            </div>
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">3. Neraca saldo</div></div>
                <div class="card-body">
                    @if ($check['balanced'])<p class="text-success mb-0"><i class="fa fa-check text-success"></i> Seimbang.</p>@else<p class="text-danger fw-bold mb-0">Tidak seimbang — periksa voucher.</p>@endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-5">
                <div class="card-header"><div class="card-title fs-5 fw-bolder">4. Rekonsiliasi kas & bank</div></div>
                <div class="card-body">
                    @foreach ($check['cash'] as $c)
                        @php
                            $recon = $c['recon'];
                            $stale = $recon && App\Support\Money::cents($recon->book_balance) !== $c['book'];
                        @endphp
                        <form method="POST" action="{{ route('period.reconcile', $period) }}" class="border rounded p-4 mb-4">
                            @csrf <input type="hidden" name="coa_account_id" value="{{ $c['account']->id }}">
                            <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                                <b>{{ $c['account']->label() }}</b>
                                <span>Saldo buku: <b>{{ App\Support\Money::format(App\Support\Money::decimal($c['book'])) }}</b></span>
                            </div>
                            @if ($recon)
                                <p class="fs-7 mb-3 {{ $stale ? 'text-danger' : 'text-success' }}">
                                    {{ $stale ? 'Saldo buku berubah sejak rekonsiliasi — ulangi.' : 'Direkonsiliasi: saldo aktual ' . App\Support\Money::format($recon->actual_balance) . ($recon->differenceCents() ? ' · selisih ' . App\Support\Money::format(App\Support\Money::decimal($recon->differenceCents())) . ' (' . $recon->note . ')' : ' · cocok') }}
                                </p>
                            @endif
                            @if ($canClose)
                                <div class="row g-2">
                                    <div class="col-sm-5"><label class="form-label fs-7" for="actual-{{ $c['account']->id }}">Saldo aktual (rekening koran/hitung fisik)</label>
                                        <input id="actual-{{ $c['account']->id }}" type="number" step="0.01" name="actual" required class="form-control form-control-sm" value="{{ $recon->actual_balance ?? App\Support\Money::decimal($c['book']) }}"></div>
                                    <div class="col-sm-5"><label class="form-label fs-7" for="note-{{ $c['account']->id }}">Keterangan selisih</label>
                                        <input id="note-{{ $c['account']->id }}" name="note" class="form-control form-control-sm" value="{{ $recon->note ?? '' }}"></div>
                                    <div class="col-sm-2 d-flex align-items-end"><button class="btn btn-sm btn-light-primary w-100">Simpan</button></div>
                                </div>
                            @endif
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <a href="{{ route('period.index', ['tahun' => $period->year]) }}" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i> Kembali</a>
            @if ($period->isClosed())
                <span class="badge badge-light-dark ms-auto">Periode ditutup {{ $period->closed_at?->translatedFormat('j M Y') }}</span>
            @elseif ($canClose)
                <form method="POST" action="{{ route('period.close', $period) }}" class="ms-auto d-flex gap-3 align-items-center" onsubmit="return confirm('Tutup periode {{ $period->label() }}? Voucher tidak bisa lagi diposting ke periode ini.')">
                    @csrf
                    @if ($check['pending'])<label class="form-check form-check-sm"><input class="form-check-input" type="checkbox" name="confirm_pending" value="1"> <span class="form-check-label">Tutup walau {{ $check['pending'] }} voucher belum diposting</span></label>@endif
                    <button class="btn btn-sm btn-dark"><i class="fa fa-lock"></i> Tutup periode</button>
                </form>
            @endif
        </div>
    </div>
</x-dashboard.page>
