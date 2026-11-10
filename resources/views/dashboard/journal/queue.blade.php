<x-dashboard.page title="Antrean Persetujuan" :crumbs="['Keuangan', 'Antrean Persetujuan']">
    @if ($journals->isEmpty())
        <div class="card"><div class="card-body text-muted">Tidak ada voucher yang menunggu persetujuan.</div></div>
    @else
        <form method="POST" action="{{ route('journal.post-many') }}" id="bulk-form" class="d-flex justify-content-between align-items-center mb-5">
            @csrf
            <label class="form-check"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('[name=&quot;ids[]&quot;]').forEach(c => c.checked = this.checked)"> <span class="form-check-label">Pilih semua ({{ $journals->count() }})</span></label>
            <button class="btn btn-sm btn-success" onclick="return confirm('Posting semua voucher terpilih? Voucher yang dibuat/diajukan oleh Anda sendiri akan ditolak sistem.')"><i class="fa fa-check-double"></i> Posting terpilih</button>
        </form>
        @foreach ($journals as $j)
            <div class="card mb-5">
                <div class="card-header flex-wrap py-3">
                    <div class="card-title gap-3">
                        <input type="checkbox" class="form-check-input" name="ids[]" value="{{ $j->id }}" form="bulk-form" aria-label="Pilih voucher {{ $j->id }}">
                        <a href="{{ route('journal.show', $j) }}" class="fw-bolder fs-5">{{ $j->voucher_type }} · {{ $j->description }}</a>
                        @if ($j->is_system)<span class="badge badge-light">sistem</span>@endif
                    </div>
                    <div class="card-toolbar gap-2">
                        <form method="POST" action="{{ route('journal.post', $j) }}">@csrf<button class="btn btn-sm btn-success">Posting</button></form>
                        <form method="POST" action="{{ route('journal.reject', $j) }}" onsubmit="const r = prompt('Alasan penolakan:'); if (!r) return false; this.reason.value = r;">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-light-danger">Tolak</button></form>
                    </div>
                </div>
                <div class="card-body row">
                    <div class="col-lg-7">
                        <div class="text-muted fs-7 mb-3">{{ $j->date->translatedFormat('j M Y') }} · dibuat {{ $j->creator?->name ?? 'sistem' }} · diajukan {{ $j->submitter?->name }}{{ $j->reference_no ? ' · ref ' . $j->reference_no : '' }}</div>
                        <table class="table table-row-bordered gs-2 gy-1 fs-7 mb-0">
                            @foreach ($j->lines as $line)
                                <tr><td>{{ $line->account->label() }}</td><td class="text-muted">{{ $line->costCenter?->code }}</td>
                                    <td class="text-end">{{ (float) $line->debit ? number_format($line->debit, 0, ',', '.') : '' }}</td><td class="text-end">{{ (float) $line->credit ? number_format($line->credit, 0, ',', '.') : '' }}</td></tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="col-lg-5">
                        @forelse ($j->attachments ?? [] as $index => $file)
                            @php($url = route('journal.attachment', [$j, $index]))
                            @if (preg_match('/\.(jpe?g|png)$/i', $file['path']))
                                <a href="{{ $url }}" target="_blank"><img src="{{ $url }}" alt="Lampiran {{ $file['name'] }}" class="mw-100 rounded border mb-2" style="max-height: 260px"></a>
                            @else
                                <a href="{{ $url }}" target="_blank" class="d-block mb-2"><i class="fa fa-file-pdf"></i> {{ $file['name'] }}</a>
                            @endif
                        @empty
                            <span class="text-muted fs-7">Tanpa lampiran (voucher sistem).</span>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</x-dashboard.page>
