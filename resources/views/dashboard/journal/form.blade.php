{{-- Form voucher (PRD 9.13.2): baris dinamis, total seimbang, lampiran. Aturan lengkap dicek server saat diajukan. --}}
@php
    $editing = $journal->exists;
    $rows = old('lines', $lines);
    $accountOptions = $accounts->map(fn ($a) => ['id' => $a->id, 'label' => "{$a->code} {$a->name}", 'needs_party' => $a->is_receivable || $a->is_payable, 'needs_center' => in_array($a->type, ['pendapatan', 'beban'])]);
@endphp
<x-dashboard.page :title="$editing ? 'Ubah Voucher' : 'Voucher Baru'" :crumbs="['Keuangan', 'Voucher', $editing ? 'Ubah' : 'Baru']">
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @if ($journal->status === 'ditolak')<div class="alert alert-light-danger">Ditolak: {{ $journal->reject_reason }}</div>@endif
    <form method="POST" action="{{ $editing ? route('journal.update', $journal) : route('journal.store') }}" enctype="multipart/form-data" id="voucher-form">
        @csrf @if ($editing) @method('PATCH') @endif
        <div class="card mb-5">
            <div class="card-body row">
                <div class="col-md-3"><x-dashboard.field name="voucher_type" label="Jenis" type="select" :options="$types->map(fn ($l, $k) => $k . ' · ' . $l)->all()" :value="$journal->voucher_type" required /></div>
                <div class="col-md-3"><x-dashboard.field name="date" label="Tanggal" type="date" :value="$journal->date" required /></div>
                <div class="col-md-3"><x-dashboard.field name="party_id" label="Pihak" type="select" :options="$parties->mapWithKeys(fn ($p) => [$p->id => $p->name])->all()" :value="$journal->party_id" placeholder="—" /></div>
                <div class="col-md-3"><x-dashboard.field name="reference_no" label="Ref. (no. nota/faktur)" :value="$journal->reference_no" /></div>
                <div class="col-md-9"><x-dashboard.field name="description" label="Uraian" :value="$journal->description" required /></div>
                <div class="col-md-3"><x-dashboard.field name="cost_center_id" label="Pusat biaya bawaan" type="select" :options="$centers->mapWithKeys(fn ($c) => [$c->id => $c->code . ' ' . $c->name])->all()" :value="$journal->cost_center_id" placeholder="—" /></div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gs-4 gy-2 mb-0" id="lines">
                        <thead><tr class="fw-bold bg-light fs-7"><th class="min-w-250px">Akun</th><th class="min-w-150px">Pusat biaya</th><th class="min-w-150px">Pihak</th><th class="min-w-150px">Memo</th><th class="min-w-125px text-end">Debit</th><th class="min-w-125px text-end">Kredit</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($rows as $i => $line)
                                @include('dashboard.journal.line', ['i' => $i, 'line' => $line])
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bolder"><td colspan="4"><button type="button" class="btn btn-sm btn-light-primary" id="add-line"><i class="fa fa-plus"></i> Baris</button>
                                <span class="ms-4" id="balance-state"></span></td>
                                <td class="text-end" id="total-debit">0</td><td class="text-end" id="total-credit">0</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <template id="line-template">@include('dashboard.journal.line', ['i' => '__I__', 'line' => []])</template>

        <div class="card mb-5">
            <div class="card-header"><div class="card-title fs-5 fw-bolder">Lampiran <span class="text-muted fs-7 ms-2">wajib saat diajukan · jpg/png/pdf maks. 5 MB</span></div></div>
            <div class="card-body">
                @foreach ($journal->attachments ?? [] as $index => $file)
                    <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="remove_attachments[]" value="{{ $index }}"> <span class="form-check-label">Hapus <a href="{{ route('journal.attachment', [$journal, $index]) }}" target="_blank">{{ $file['name'] }}</a></span></label>
                @endforeach
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" capture="environment" class="form-control mt-3" aria-label="Unggah lampiran">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ $editing ? route('journal.show', $journal) : route('journal.index') }}" class="btn btn-sm btn-light">Batal</a>
            <button class="btn btn-sm btn-light-primary" name="action" value="draft">Simpan draf</button>
            @can('journal-submit')<button class="btn btn-sm btn-primary" name="action" value="submit">Simpan & ajukan</button>@endcan
        </div>
    </form>
    <x-slot name="javascript">
        <script>
            (function () {
                const accounts = @json($accountOptions->keyBy('id'));
                const tbody = document.querySelector('#lines tbody');
                let next = {{ count($rows) }};
                const cents = (v) => Math.round((parseFloat(v) || 0) * 100);
                const rupiah = (c) => (c / 100).toLocaleString('id-ID', { minimumFractionDigits: 0 });
                function refresh() {
                    let d = 0, c = 0;
                    tbody.querySelectorAll('tr').forEach((tr) => {
                        d += cents(tr.querySelector('[data-debit]').value); c += cents(tr.querySelector('[data-credit]').value);
                        const acc = accounts[tr.querySelector('[data-account]').value];
                        tr.querySelector('[data-party]').classList.toggle('border-warning', !!(acc && acc.needs_party && !tr.querySelector('[data-party]').value));
                        tr.querySelector('[data-center]').classList.toggle('border-warning', !!(acc && acc.needs_center && !tr.querySelector('[data-center]').value && !document.getElementById('field-cost_center_id').value));
                    });
                    document.getElementById('total-debit').textContent = rupiah(d);
                    document.getElementById('total-credit').textContent = rupiah(c);
                    const state = document.getElementById('balance-state');
                    state.textContent = d === c && d > 0 ? 'Seimbang ✓' : 'Selisih ' + rupiah(Math.abs(d - c));
                    state.className = 'ms-4 ' + (d === c && d > 0 ? 'text-success' : 'text-danger');
                }
                document.getElementById('add-line').addEventListener('click', () => {
                    tbody.insertAdjacentHTML('beforeend', document.getElementById('line-template').innerHTML.replaceAll('__I__', next++));
                });
                tbody.addEventListener('click', (e) => { if (e.target.closest('[data-remove]')) { e.target.closest('tr').remove(); refresh(); } });
                document.getElementById('voucher-form').addEventListener('input', refresh);
                document.getElementById('voucher-form').addEventListener('change', refresh);
                refresh();
            })();
        </script>
    </x-slot>
</x-dashboard.page>
