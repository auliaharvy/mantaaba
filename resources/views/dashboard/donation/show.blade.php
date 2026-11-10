<x-dashboard.page :title="'Donasi ' . ($donation->receipt_number ?? '#' . $donation->id)" :crumbs="['Keuangan', 'Donasi', 'Rincian']">
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <table class="table table-row-bordered gy-3 mb-0">
                        <tr><td class="text-muted w-200px">Status</td><td><span class="badge {{ ['menunggu' => 'badge-light-warning', 'terverifikasi' => 'badge-light-success', 'ditolak' => 'badge-light-danger'][$donation->status] }}">{{ App\Models\Donation::STATUSES[$donation->status] }}</span>
                            @if ($donation->reject_reason)<div class="fs-7 text-danger mt-1">{{ $donation->reject_reason }}</div>@endif</td></tr>
                        <tr><td class="text-muted">Donatur</td><td><b>{{ $donation->donor_name }}</b>{{ $donation->is_anonymous ? ' (ditampilkan "Hamba Allah")' : '' }}@if ($donation->party)<div class="fs-8 text-muted">Pihak {{ $donation->party->code }}</div>@endif</td></tr>
                        <tr><td class="text-muted">Kontak</td><td>{{ $donation->phone ?: '-' }} · {{ $donation->email ?: '-' }}</td></tr>
                        <tr><td class="text-muted">Jenis</td><td>{{ App\Models\Donation::KINDS[$donation->kind] }}{{ $donation->item_description ? ' · ' . $donation->item_description : '' }}</td></tr>
                        <tr><td class="text-muted">Jumlah</td><td class="fs-4 fw-bolder">{{ App\Support\Money::format($donation->amount) }}</td></tr>
                        <tr><td class="text-muted">Tanggal</td><td>{{ $donation->transferred_at->translatedFormat('j F Y') }}</td></tr>
                        <tr><td class="text-muted">Rekening/akun</td><td>{{ $donation->account?->label() ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Peruntukan</td><td>{{ $donation->purposeLabel() }}</td></tr>
                        <tr><td class="text-muted">Pesan</td><td>{{ $donation->message ?: '-' }}</td></tr>
                        <tr><td class="text-muted">Voucher</td><td>@if ($donation->journal)<a href="{{ route('journal.show', $donation->journal) }}">{{ $donation->journal->number ?? 'diajukan' }}</a> · {{ $donation->journal->status }}@else - @endif</td></tr>
                    </table>
                </div>
                <div class="card-footer d-flex flex-wrap gap-2">
                    <a href="{{ route('donation.index') }}" class="btn btn-sm btn-light"><i class="fa fa-arrow-left"></i> Kembali</a>
                    @if ($receiptUrl)<a href="{{ $receiptUrl }}" class="btn btn-sm btn-light-primary"><i class="fa fa-file-pdf"></i> Bukti terima</a>
                        @if ($donation->phone)<a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $donation->phone)) }}?text={{ rawurlencode('Jazakumullahu khairan atas donasi Anda. Bukti terima: ' . $receiptUrl) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light-success"><i class="fab fa-whatsapp"></i> Kirim via WA</a>@endif
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            @if ($donation->proof)
                <div class="card mb-5"><div class="card-header"><div class="card-title fs-5">Bukti</div></div><div class="card-body text-center">
                    @if (str_ends_with(strtolower($donation->proof), '.pdf'))<a href="{{ route('donation.proof', $donation) }}" target="_blank" class="btn btn-light-primary"><i class="fa fa-file-pdf"></i> Buka PDF</a>
                    @else<a href="{{ route('donation.proof', $donation) }}" target="_blank"><img src="{{ route('donation.proof', $donation) }}" alt="Bukti transfer" class="img-fluid rounded"></a>@endif
                </div></div>
            @endif
            @if ($donation->status === 'menunggu' && auth()->user()->can('donation-verify'))
                <form method="POST" action="{{ route('donation.verify', $donation) }}" class="card mb-5">
                    @csrf
                    <div class="card-body">
                        <x-dashboard.field name="coa_bank_account_id" label="Diterima di akun" type="select" required :value="$donation->coa_bank_account_id" :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->label()])->all()" help="Cocokkan dengan mutasi rekening sebelum verifikasi." />
                        <button class="btn btn-success w-100"><i class="fa fa-check"></i> Verifikasi & buat voucher</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('donation.reject', $donation) }}" class="card">
                    @csrf
                    <div class="card-body">
                        <x-dashboard.field name="reason" label="Alasan penolakan" type="textarea" required />
                        <button class="btn btn-light-danger w-100">Tolak</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-dashboard.page>
