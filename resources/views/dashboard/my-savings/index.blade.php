@php
    $rp = fn ($cents) => App\Support\Money::format(App\Support\Money::decimal($cents));
@endphp
<x-dashboard.page title="Titipan Saya" :crumbs="['Saya', 'Titipan Saya']">
    <div class="card mb-5">
        <div class="card-body d-flex flex-wrap align-items-center gap-5">
            <span class="symbol symbol-60px"><span class="symbol-label bg-light-primary"><i class="fa fa-piggy-bank fs-1 text-primary"></i></span></span>
            <div>
                <div class="text-muted fs-7">Saldo titipan {{ $member->name }}</div>
                <div class="fs-2hx fw-bolder {{ $balance < 0 ? 'text-danger' : 'text-dark' }}">{{ $rp($balance) }}</div>
                <div class="text-muted fs-8">Sub-akun {{ $account->code }}-{{ $party?->code ?? $member->member_number }} · {{ $account->name }}</div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title fs-5 fw-bolder">Riwayat setoran & pengambilan</div></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-row-bordered align-middle gs-5 gy-3 mb-0 fs-7">
                <thead><tr class="fw-bold bg-light"><th>Tanggal</th><th>Uraian</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th class="text-end">Saldo</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="text-nowrap">{{ $row['date']->translatedFormat('j M Y') }}<div class="text-muted fs-8">{{ $row['number'] }}</div></td>
                            <td>{{ $row['description'] }}</td>
                            <td class="text-end text-success">{{ $row['in'] ? $rp($row['in']) : '' }}</td>
                            <td class="text-end text-danger">{{ $row['out'] ? $rp($row['out']) : '' }}</td>
                            <td class="text-end fw-bold">{{ $rp($row['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-10">Belum ada titipan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-muted fs-8 mt-4">Hanya transaksi yang sudah disetujui Bendahara yang tampil. Hubungi Bendahara untuk menyetor atau mengambil titipan.</p>
</x-dashboard.page>
