<x-dashboard.page title="Pembelian Stok" :crumbs="['Operasional', 'Pembelian']">
    <div class="card">
        <div class="card-header"><div class="card-title fs-5 fw-bolder">Pembelian</div>
            <div class="card-toolbar">@can('stock-move')<a href="{{ route('stock.purchase-form') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Catat pembelian</a>@endcan</div></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0 fs-7">
                <thead><tr class="fw-bold bg-light"><th>Tanggal</th><th>Voucher</th><th>Pemasok</th><th>Barang</th><th class="text-end">Total</th><th></th></tr></thead>
                <tbody>
                    @forelse ($journals as $j)
                        @php($lines = $moves[$j->id] ?? collect())
                        <tr>
                            <td>{{ $j->date->format('d/m/Y') }}</td>
                            <td><a href="{{ route('journal.show', $j) }}">{{ $j->displayNumber() }}</a><div><span class="badge badge-{{ App\Models\Journal::STATUS_COLORS[$j->status] }}">{{ App\Models\Journal::STATUSES[$j->status] }}</span></div></td>
                            <td>{{ $j->party?->name ?? '-' }}</td>
                            <td>@forelse ($lines as $m){{ $m->item->name }} {{ App\Services\StockLedger::qty((float) $m->qty) }} {{ $m->item->unit }}@unless ($loop->last), @endunless @empty<span class="text-muted">dibatalkan</span>@endforelse</td>
                            <td class="text-end">{{ App\Support\Money::format($lines->sum('total_cost')) }}</td>
                            <td class="text-end">
                                @if ($lines->isNotEmpty() && $j->status !== 'terposting' && auth()->user()->can('stock-move'))
                                    <form method="POST" action="{{ route('stock.purchase-cancel', $j) }}" onsubmit="return confirm('Batalkan pembelian ini? Stok dikurangi kembali dan voucher ditolak.')">@csrf<button class="btn btn-sm btn-light-danger">Batalkan</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-10">Belum ada pembelian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $journals->links() }}</div>
</x-dashboard.page>
