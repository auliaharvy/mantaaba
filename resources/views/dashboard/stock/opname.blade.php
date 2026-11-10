@php
    $final = $opname->status === 'final';
    $editable = ! $final && auth()->user()->can('stock-opname');
@endphp
<x-dashboard.page :title="'Opname ' . $opname->warehouse->name" :crumbs="['Operasional', 'Stock Opname', $opname->date->format('d/m/Y')]">
    <form method="POST" action="{{ route('stock.opname-save', $opname) }}" class="card">
        @csrf
        <div class="card-header"><div class="card-title fs-5 fw-bolder">{{ $opname->warehouse->name }} · {{ $opname->date->translatedFormat('j F Y') }}</div>
            <div class="card-toolbar"><span class="badge {{ $final ? 'badge-light-success' : 'badge-light-warning' }}">{{ $final ? 'Final' : 'Draf' }}</span></div></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0">
                <thead><tr class="fw-bold bg-light fs-7"><th>Barang</th><th class="text-end">Sistem</th><th class="w-150px">Fisik</th><th class="text-end">Selisih</th></tr></thead>
                <tbody>
                    @foreach ($opname->lines as $line)
                        @php
                            $item = $items[$line['item_id']] ?? null;
                            $physical = old('physical.' . $line['item_id'], $line['physical']);
                            $diff = $physical !== null && $physical !== '' ? round((float) $physical - $line['system'], 3) : null;
                        @endphp
                        @continue(! $item)
                        <tr>
                            <td>{{ $item->name }} <span class="text-muted fs-8">{{ $item->unit }}</span></td>
                            <td class="text-end">{{ App\Services\StockLedger::qty($line['system']) }}</td>
                            <td>@if ($editable)<input type="number" step="0.001" min="0" name="physical[{{ $item->id }}]" value="{{ $physical }}" class="form-control form-control-sm" aria-label="Fisik {{ $item->name }}">@else{{ $physical !== null ? App\Services\StockLedger::qty((float) $physical) : '-' }}@endif</td>
                            <td class="text-end {{ $diff < 0 ? 'text-danger' : ($diff > 0 ? 'text-success' : '') }}">{{ $diff !== null ? App\Services\StockLedger::qty($diff) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-wrap gap-3 align-items-center">
            <a href="{{ route('stock.opnames') }}" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i></a>
            @if ($editable)
                <input name="note" value="{{ $opname->note }}" class="form-control form-control-sm w-250px" placeholder="Catatan" aria-label="Catatan opname">
                <span class="text-muted fs-7 me-auto">Kosongkan barang yang tidak dihitung. Saat final, selisih dihitung dari saldo sistem terbaru.</span>
                <button class="btn btn-sm btn-light-primary">Simpan draf</button>
                <button class="btn btn-sm btn-primary" name="finalize" value="1" onclick="return confirm('Finalkan opname? Selisih langsung menjadi mutasi stok.')">Finalkan</button>
            @elseif ($final)
                <span class="text-muted fs-7">{{ $opname->note }}</span>
            @endif
        </div>
    </form>
</x-dashboard.page>
