@php
    $j = $journal;
    $user = auth()->user();
@endphp
<x-dashboard.page :title="$j->displayNumber()" :crumbs="['Keuangan', 'Voucher', $j->displayNumber()]">
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <div class="card mb-5">
        <div class="card-header flex-wrap py-3">
            <div class="card-title flex-column align-items-start">
                <span class="fs-3 fw-bolder">{{ $j->voucher_type }} · {{ $j->displayNumber() }} <span class="badge badge-{{ App\Models\Journal::STATUS_COLORS[$j->status] }} ms-2">{{ App\Models\Journal::STATUSES[$j->status] }}</span>@if ($j->is_system)<span class="badge badge-light ms-1">sistem</span>@endif</span>
                <span class="text-muted fs-7">{{ App\Models\Journal::TYPES[$j->voucher_type] }} · {{ $j->date->translatedFormat('j F Y') }} · periode {{ $j->period->label() }}{{ $j->period->isClosed() ? ' (ditutup)' : '' }}</span>
            </div>
            <div class="card-toolbar d-flex flex-wrap gap-2">
                <a href="{{ route('journal.pdf', $j) }}" class="btn btn-sm btn-light"><i class="fa fa-print"></i> PDF</a>
                @can('update', $j)<a href="{{ route('journal.edit', $j) }}" class="btn btn-sm btn-light-success"><i class="fa fa-pen"></i> Ubah</a>@endcan
                @can('delete', $j)<button type="button" class="btn btn-sm btn-light-danger" data-delete-url="{{ route('journal.destroy', $j) }}" data-confirm="Draf voucher dihapus permanen.">Hapus</button>@endcan
                @if ($j->isEditable() && $user->can('journal-submit'))
                    <form method="POST" action="{{ route('journal.submit', $j) }}">@csrf<button class="btn btn-sm btn-primary">Ajukan</button></form>
                @endif
                @if ($j->status === 'diajukan' && $user->can('journal-approve'))
                    <form method="POST" action="{{ route('journal.post', $j) }}">@csrf<button class="btn btn-sm btn-success"><i class="fa fa-check"></i> Posting</button></form>
                    <form method="POST" action="{{ route('journal.reject', $j) }}" onsubmit="const r = prompt('Alasan penolakan:'); if (!r) return false; this.reason.value = r;">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-light-danger">Tolak</button></form>
                @endif
                @if ($j->status === 'terposting' && $user->can('journal-approve'))
                    <form method="POST" action="{{ route('journal.reverse', $j) }}" onsubmit="return confirm('Balik voucher {{ $j->number }}? Voucher pembalik langsung terposting dan saldo kembali.')">@csrf<button class="btn btn-sm btn-light-dark"><i class="fa fa-undo"></i> Balik</button></form>
                @endif
                <a href="{{ route('journal.index') }}" class="btn btn-sm btn-light-primary"><i class="fa fa-arrow-left"></i></a>
            </div>
        </div>
        <div class="card-body">
            @if ($j->status === 'ditolak')<div class="alert alert-light-danger">Ditolak: {{ $j->reject_reason }}</div>@endif
            @if ($j->reversalOf)<div class="alert alert-light-info">Pembalik untuk <a href="{{ route('journal.show', $j->reversalOf) }}">{{ $j->reversalOf->number }}</a>.</div>@endif
            @if ($j->reversal)<div class="alert alert-light-dark">Dibalik oleh <a href="{{ route('journal.show', $j->reversal) }}">{{ $j->reversal->number }}</a>.</div>@endif
            <div class="row mb-5 fs-6">
                <div class="col-md-6"><div class="text-muted fs-7">Uraian</div><div class="fw-bold">{{ $j->description }}</div></div>
                <div class="col-md-3"><div class="text-muted fs-7">Pihak</div><div>{{ $j->party?->name ?? '-' }}</div></div>
                <div class="col-md-3"><div class="text-muted fs-7">Referensi</div><div>{{ $j->reference_no ?: '-' }}</div></div>
            </div>
            <div class="table-responsive">
                <table class="table table-row-bordered gs-3 gy-2 fs-7">
                    <thead><tr class="fw-bold bg-light"><th>Akun</th><th>Pusat biaya</th><th>Pihak</th><th>Memo</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead>
                    <tbody>
                        @foreach ($j->lines as $line)
                            <tr><td>@can('finance-report-view')<a href="{{ route('ledger.index', ['account' => $line->coa_account_id, 'from' => $j->date->copy()->startOfMonth()->toDateString(), 'to' => $j->date->toDateString()]) }}">{{ $line->account->label() }}</a>@else{{ $line->account->label() }}@endcan</td>
                                <td>{{ $line->costCenter?->code ?? '-' }}</td><td>{{ $line->party?->name ?? '-' }}</td><td>{{ $line->memo }}</td>
                                <td class="text-end">{{ (float) $line->debit ? number_format($line->debit, 0, ',', '.') : '' }}</td><td class="text-end">{{ (float) $line->credit ? number_format($line->credit, 0, ',', '.') : '' }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="fw-bolder"><td colspan="4">Total</td><td class="text-end">{{ number_format($j->lines->sum('debit'), 0, ',', '.') }}</td><td class="text-end">{{ number_format($j->lines->sum('credit'), 0, ',', '.') }}</td></tr></tfoot>
                </table>
            </div>
            <div class="row fs-7 text-muted">
                <div class="col-md-4">Dibuat: {{ $j->creator?->name ?? 'Sistem' }} · {{ $j->created_at->translatedFormat('j M Y H:i') }}</div>
                <div class="col-md-4">Diajukan: {{ $j->submitter?->name ?? '-' }}{{ $j->submitted_at ? ' · ' . $j->submitted_at->translatedFormat('j M Y H:i') : '' }}</div>
                <div class="col-md-4">Disetujui: {{ $j->approver?->name ?? '-' }}{{ $j->posted_at ? ' · ' . $j->posted_at->translatedFormat('j M Y H:i') : '' }}</div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title fs-5 fw-bolder">Lampiran</div></div>
        <div class="card-body">
            @forelse ($j->attachments ?? [] as $index => $file)
                @php $url = route('journal.attachment', [$j, $index]); @endphp
                <div class="mb-4">
                    <a href="{{ $url }}" target="_blank"><i class="fa fa-paperclip"></i> {{ $file['name'] }}</a>
                    @if (preg_match('/\.(jpe?g|png)$/i', $file['path']))<img src="{{ $url }}" alt="Lampiran {{ $file['name'] }}" class="d-block mt-2 mw-100 rounded border" style="max-height: 420px">@endif
                </div>
            @empty
                <p class="text-muted mb-0">{{ $j->is_system ? 'Voucher sistem: dokumen sumber ada di transaksi asal.' : 'Belum ada lampiran. Wajib saat diajukan.' }}</p>
            @endforelse
        </div>
    </div>
</x-dashboard.page>
