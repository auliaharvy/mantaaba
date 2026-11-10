{{-- Laporan keuangan PDF (PRD 7.3, 9.14): kop, isi laporan, penandatangan dari Setting. --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'Helvetica', sans-serif; color: #2b1d12; font-size: 10.5px; }
    .kop { border-bottom: 2px solid #C9A04A; padding-bottom: 6px; margin-bottom: 10px; }
    .kop img { height: 44px; vertical-align: middle; }
    .kop .name { display: inline-block; vertical-align: middle; margin-left: 8px; }
    h1 { font-size: 15px; color: #5B3A1F; margin: 0 0 2px; text-align: center; }
    p.text-muted { text-align: center; margin: 0 0 10px; color: #6e5b4b; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 3px 4px; border-bottom: 1px solid #eee5d8; text-align: left; vertical-align: top; }
    th { background: #F7F1E6; }
    .n { text-align: right; white-space: nowrap; }
    .sub { padding-left: 14px; }
    .total td { font-weight: bold; background: #faf6ef; }
    a { color: #2b1d12; text-decoration: none; }
    .sign { width: 100%; margin-top: 28px; }
    .sign td { width: 50%; text-align: center; border: 0; }
    .sign .line { margin: 46px 40px 0; border-top: 1px solid #2b1d12; padding-top: 2px; }
</style>
</head>
<body>
    <div class="kop">
        <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('media/logos/logo-mantaaba-512.jpg'))) }}" alt="">
        <span class="name"><b style="font-size: 13px; color: #5B3A1F">{{ strtoupper($site->site_name) }}</b><br>{{ $site->foundation_name }}{{ $site->fullAddress() ? ' · ' . $site->fullAddress() : '' }}</span>
    </div>
    <h1>{{ strtoupper($title) }}</h1>
    @include('dashboard.report.body', ['links' => false])
    <table class="sign">
        <tr>
            @foreach (['ketua' => 'Ketua', 'bendahara' => 'Bendahara'] as $key => $role)
                <td>{{ $role }}<div class="line">{{ $site->report_signatories[$key] ?? '(......................)' }}</div></td>
            @endforeach
        </tr>
    </table>
    <p style="font-size: 8px; color: #999; margin-top: 12px">Dicetak {{ now()->translatedFormat('j F Y H:i') }} dari sistem {{ $site->site_name }}.</p>
</body>
</html>
