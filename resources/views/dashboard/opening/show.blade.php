{{-- Wizard saldo awal (PRD 9.13.3): saldo di sisi normal akun; selisih debit-kredit wajib 0. --}}
<x-dashboard.page title="Saldo Awal" :crumbs="['Keuangan', 'Periode', 'Saldo Awal']">
    @if ($existing)
        <div class="alert alert-light-success">Saldo awal sudah dibuat: <a href="{{ route('journal.show', $existing) }}" class="fw-bold">{{ $existing->displayNumber() }}</a> per {{ $existing->date->translatedFormat('j F Y') }}. Untuk mengoreksi, balik voucher tersebut lalu isi ulang.</div>
    @else
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('opening.store') }}" id="opening-form">
            @csrf
            <div class="card mb-5">
                <div class="card-body d-flex flex-wrap gap-5 align-items-end">
                    <div><x-dashboard.field name="date" label="Tanggal mulai pembukuan" type="date" :value="$date ?? today()->startOfYear()" required /></div>
                    <div class="mb-6 fs-5">Debit <strong id="sum-debit">0</strong> · Kredit <strong id="sum-credit">0</strong> · <span id="diff" class="fw-bolder">Selisih 0</span></div>
                </div>
            </div>
            <div class="row g-5">
                <div class="col-xl-7">
                    <div class="card">
                        <div class="card-header"><div class="card-title fs-5 fw-bolder">Saldo per akun</div></div>
                        <div class="card-body p-0">
                            <table class="table table-row-bordered align-middle gs-5 gy-2 mb-0 fs-7">
                                <tbody>
                                    @foreach ($accounts as $a)
                                        <tr><td>{{ $a->code }}</td><td>{{ $a->name }}</td><td class="text-muted">{{ $a->normal_balance }}</td>
                                            <td class="w-175px"><input type="number" step="0.01" min="0" name="amounts[{{ $a->id }}]" value="{{ old("amounts.{$a->id}") }}" data-side="{{ $a->normal_balance }}" class="form-control form-control-sm text-end amount" aria-label="Saldo {{ $a->name }}"></td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="card">
                        <div class="card-header"><div class="card-title fs-5 fw-bolder">Piutang & utang per pihak</div></div>
                        <div class="card-body">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="d-flex gap-2 mb-3">
                                    <select name="party_rows[{{ $i }}][coa_account_id]" class="form-select form-select-sm party-account" aria-label="Akun"><option value="">Akun</option>@foreach ($partyAccounts as $a)<option value="{{ $a->id }}" data-side="{{ $a->normal_balance }}">{{ $a->code }} {{ $a->name }}</option>@endforeach</select>
                                    <select name="party_rows[{{ $i }}][party_id]" class="form-select form-select-sm" aria-label="Pihak"><option value="">Pihak</option>@foreach ($parties as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
                                    <input type="number" step="0.01" min="0" name="party_rows[{{ $i }}][amount]" class="form-control form-control-sm text-end w-125px party-amount" aria-label="Jumlah">
                                </div>
                            @endfor
                            <div class="form-text">Pihak baru ditambahkan di Keuangan → COA & Master → Pihak.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-end mt-5"><button class="btn btn-primary" onclick="return confirm('Posting saldo awal? Selisih harus 0.')">Posting saldo awal</button></div>
        </form>
        <x-slot name="javascript">
            <script>
                function recalc() {
                    let d = 0, c = 0;
                    const add = (side, v) => { v = Math.round((parseFloat(v) || 0) * 100); if (side === 'debit') d += v; else c += v; };
                    document.querySelectorAll('.amount').forEach((i) => add(i.dataset.side, i.value));
                    document.querySelectorAll('.party-amount').forEach((i) => { const s = i.parentElement.querySelector('.party-account'); if (s.value) add(s.selectedOptions[0].dataset.side, i.value); });
                    const f = (n) => (n / 100).toLocaleString('id-ID');
                    document.getElementById('sum-debit').textContent = f(d);
                    document.getElementById('sum-credit').textContent = f(c);
                    const diff = document.getElementById('diff');
                    diff.textContent = d === c ? 'Seimbang ✓' : 'Selisih ' + f(Math.abs(d - c));
                    diff.className = 'fw-bolder ' + (d === c ? 'text-success' : 'text-danger');
                }
                document.getElementById('opening-form').addEventListener('input', recalc);
                recalc();
            </script>
        </x-slot>
    @endif
</x-dashboard.page>
