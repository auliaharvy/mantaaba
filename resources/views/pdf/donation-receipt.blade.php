<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'Helvetica', sans-serif; color: #2b1d12; font-size: 12px; }
    .frame { border: 2px solid #5B3A1F; padding: 16px 22px; }
    .kop { border-bottom: 2px solid #C9A04A; padding-bottom: 6px; margin-bottom: 12px; }
    .kop img { height: 40px; vertical-align: middle; }
    h1 { font-size: 17px; color: #5B3A1F; margin: 6px 0; }
    td { padding: 4px 6px 4px 0; vertical-align: top; }
    .amount { font-size: 20px; font-weight: bold; color: #5B3A1F; }
</style>
</head>
<body>
<div class="frame">
    <div class="kop"><img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('media/logos/logo-mantaaba-512.jpg'))) }}" alt="">
        <b style="color: #5B3A1F; margin-left: 8px">{{ strtoupper($site->site_name) }}</b> · {{ $site->foundation_name }}</div>
    <h1>BUKTI TERIMA DONASI</h1>
    <table>
        <tr><td>Nomor</td><td><b>{{ $donation->receipt_number }}</b></td></tr>
        <tr><td>Telah diterima dari</td><td><b>{{ $donation->displayName() }}</b></td></tr>
        <tr><td>Sejumlah</td><td class="amount">{{ App\Support\Money::format($donation->amount) }}</td></tr>
        <tr><td>Peruntukan</td><td>{{ $donation->purposeLabel() }}{{ $donation->kind === 'barang' ? ' · ' . $donation->item_description : '' }}</td></tr>
        <tr><td>Tanggal</td><td>{{ $donation->transferred_at->translatedFormat('j F Y') }} · dicatat {{ $donation->journal->number }}</td></tr>
    </table>
    <p>Jazakumullahu khairan katsiran. Semoga Allah membalas kebaikan Anda dengan keberkahan.</p>
    <p style="text-align: right; margin-top: 18px">Bendahara<br><br><br><b>{{ $site->report_signatories['bendahara'] ?? '(......................)' }}</b></p>
</div>
</body>
</html>
