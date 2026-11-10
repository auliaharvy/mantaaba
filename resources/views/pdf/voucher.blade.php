{{-- Cetak voucher (PRD 9.13.2): kop, nomor, baris, dibuat/diajukan/disetujui. --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'Helvetica', sans-serif; color: #2b1d12; font-size: 11px; }
    .kop { border-bottom: 2px solid #C9A04A; padding-bottom: 6px; margin-bottom: 14px; }
    .kop img { height: 44px; vertical-align: middle; }
    .kop .name { display: inline-block; vertical-align: middle; margin-left: 8px; }
    h1 { font-size: 16px; color: #5B3A1F; margin: 0; }
    .meta td { padding: 2px 8px 2px 0; }
    table.lines { width: 100%; border-collapse: collapse; margin-top: 12px; }
    table.lines th, table.lines td { border: 1px solid #e8dccb; padding: 5px; }
    table.lines th { background: #F7F1E6; text-align: left; }
    .n { text-align: right; }
    .sign { width: 100%; margin-top: 30px; }
    .sign td { width: 33%; text-align: center; vertical-align: top; }
    .sign .line { margin: 40px 20px 0; border-top: 1px solid #2b1d12; padding-top: 3px; }
</style>
</head>
<body>
    <div class="kop">
        <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('media/logos/logo-mantaaba-512.jpg'))) }}" alt="">
        <span class="name"><b style="font-size: 13px; color: #5B3A1F">{{ strtoupper($site->site_name) }}</b><br>{{ $site->foundation_name }}</span>
    </div>
    <h1>{{ App\Models\Journal::TYPES[$journal->voucher_type] }} ({{ $journal->voucher_type }})</h1>
    <table class="meta">
        <tr><td>Nomor</td><td><b>{{ $journal->displayNumber() }}</b></td><td>Tanggal</td><td>{{ $journal->date->translatedFormat('j F Y') }}</td></tr>
        <tr><td>Pihak</td><td>{{ $journal->party?->name ?? '-' }}</td><td>Referensi</td><td>{{ $journal->reference_no ?: '-' }}</td></tr>
        <tr><td>Uraian</td><td colspan="3">{{ $journal->description }}</td></tr>
        <tr><td>Status</td><td colspan="3">{{ App\Models\Journal::STATUSES[$journal->status] }}</td></tr>
    </table>
    <table class="lines">
        <tr><th>Akun</th><th>Pusat biaya</th><th>Memo</th><th class="n">Debit</th><th class="n">Kredit</th></tr>
        @foreach ($journal->lines as $line)
            <tr><td>{{ $line->account->label() }}{{ $line->party ? ' · ' . $line->party->name : '' }}</td><td>{{ $line->costCenter?->code }}</td><td>{{ $line->memo }}</td>
                <td class="n">{{ (float) $line->debit ? number_format($line->debit, 0, ',', '.') : '' }}</td><td class="n">{{ (float) $line->credit ? number_format($line->credit, 0, ',', '.') : '' }}</td></tr>
        @endforeach
        <tr><th colspan="3">Total</th><th class="n">{{ number_format($journal->lines->sum('debit'), 0, ',', '.') }}</th><th class="n">{{ number_format($journal->lines->sum('credit'), 0, ',', '.') }}</th></tr>
    </table>
    <table class="sign">
        <tr>
            <td>Dibuat<div class="line">{{ $journal->creator?->name ?? 'Sistem' }}<br>{{ $journal->created_at->translatedFormat('j M Y') }}</div></td>
            <td>Diajukan<div class="line">{{ $journal->submitter?->name ?? '-' }}<br>{{ $journal->submitted_at?->translatedFormat('j M Y') }}</div></td>
            <td>Disetujui<div class="line">{{ $journal->approver?->name ?? '-' }}<br>{{ $journal->posted_at?->translatedFormat('j M Y') }}</div></td>
        </tr>
    </table>
</body>
</html>
