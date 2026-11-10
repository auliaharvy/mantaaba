{{-- Sertifikat A4 landscape (dompdf, PRD 9.7): skema atau kelulusan inkubasi. Halaman 2: rincian nilai. --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'Helvetica', sans-serif; color: #2b1d12; }
    .page { position: relative; width: 297mm; height: 210mm; page-break-after: always; }
    .page:last-child { page-break-after: auto; }
    .bg { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
    .frame { position: absolute; top: 9mm; left: 9mm; right: 9mm; bottom: 9mm; border: 2.5px solid #5B3A1F; }
    .frame2 { position: absolute; top: 12mm; left: 12mm; right: 12mm; bottom: 12mm; border: 1px solid #C9A04A; }
    .content { position: absolute; top: 22mm; left: 25mm; right: 25mm; text-align: center; }
    .logo { height: 22mm; }
    .org { font-size: 12px; letter-spacing: 3px; color: #5B3A1F; margin-top: 2mm; }
    .title { font-size: 38px; font-weight: bold; letter-spacing: 8px; color: #5B3A1F; margin: 4mm 0 0; }
    .number { font-size: 12px; margin-top: 2mm; }
    .given { font-size: 14px; margin-top: 8mm; }
    .name { font-size: 30px; font-weight: bold; margin: 3mm 0 1mm; border-bottom: 1px solid #C9A04A; display: inline-block; padding: 0 12mm 2mm; }
    .member { font-size: 12px; color: #6e5b4b; }
    .as { font-size: 14px; margin-top: 5mm; }
    .program { font-size: 21px; font-weight: bold; color: #8A2D1E; margin-top: 2mm; }
    .meta { font-size: 13px; margin-top: 3mm; }
    .signs { position: absolute; bottom: 20mm; left: 25mm; right: 65mm; }
    .sign { display: inline-block; width: 45%; text-align: center; vertical-align: bottom; font-size: 12px; }
    .sign img { height: 16mm; }
    .sign .who { font-weight: bold; border-top: 1px solid #2b1d12; padding-top: 1mm; margin: 0 8mm; }
    .qr { position: absolute; bottom: 17mm; right: 20mm; width: 34mm; text-align: center; font-size: 7px; word-wrap: break-word; }
    .qr img { width: 28mm; height: 28mm; }
    h2 { color: #5B3A1F; font-size: 20px; margin: 0 0 6mm; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { border-bottom: 1px solid #e8dccb; padding: 3mm 2mm; text-align: left; }
    th { background: #F7F1E6; }
    td.n { text-align: right; }
</style>
</head>
<body>
@php($logo = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(public_path('media/logos/logo-mantaaba-512.jpg'))))
<div class="page">
    @if ($background)<img class="bg" src="{{ $background }}" alt="">@else<div class="frame"></div><div class="frame2"></div>@endif
    <div class="content">
        <img class="logo" src="{{ $logo }}" alt="">
        <div class="org">{{ strtoupper($site->site_name) }} · {{ strtoupper($site->foundation_name) }}</div>
        <p class="title">SERTIFIKAT</p>
        <div class="number">Nomor: {{ $certificate->number }}</div>
        <div class="given">diberikan kepada</div>
        <div class="name">{{ $member->name }}</div>
        <div class="member">{{ $member->member_number }}{{ $member->batch ? ' · Angkatan ' . $member->batch->code : '' }}</div>
        @if ($certificate->isIncubation())
            <div class="as">telah menyelesaikan seluruh skema dan dinyatakan</div>
            <div class="program">LULUS INKUBASI KARAKTER & VOKASI BISNIS</div>
            <div class="meta">Fundamental · SIM · SIA · SIP · Teknologi Bisnis</div>
        @else
            <div class="as">telah menyelesaikan dan dinyatakan lulus</div>
            <div class="program">Skema {{ $skema->name }}</div>
            <div class="meta">Nilai akhir {{ number_format($result->final_score, 2, ',', '.') }} · Predikat {{ $result->predicate }}</div>
        @endif
        <div class="meta">{{ ($certificate->issued_at ?? now())->translatedFormat('j F Y') }}</div>
    </div>
    <div class="signs">
        @foreach ($signatories as $s)
            <div class="sign">
                @if ($s['signature_uri'])<img src="{{ $s['signature_uri'] }}" alt="">@else<div style="height: 16mm"></div>@endif
                <div class="who">{{ $s['name'] }}</div>
                <div>{{ $s['title'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
    <div class="qr"><img src="{{ $qr }}" alt=""><div>Verifikasi: {{ $verifyUrl }}</div></div>
</div>

<div class="page">
    <div class="frame"></div>
    <div class="content" style="text-align: left; top: 22mm">
        @if ($certificate->isIncubation())
            <h2>Rincian Nilai Skema · {{ $member->name }}</h2>
            <table>
                <tr><th>Skema</th><th>Knowledge</th><th>Skill</th><th>Character</th><th>Social Cohesion</th><th>Akhir</th><th>Predikat</th></tr>
                @foreach ($skemaResults as [$s, $r])
                    <tr><td>{{ $s->code }} · {{ $s->name }}</td>
                        @foreach (['knowledge', 'skill', 'character', 'social_cohesion', 'final_score'] as $f)<td class="n">{{ $r ? number_format($r->{$f}, 2, ',', '.') : '-' }}</td>@endforeach
                        <td>{{ $r?->predicate ?? '-' }}</td></tr>
                @endforeach
            </table>
        @else
            <h2>Rincian Penilaian 4 Dimensi · Skema {{ $skema->name }}</h2>
            <table>
                @php($weights = App\Services\SkemaScore::weights())
                <tr><th>Dimensi</th><th>Bobot</th><th>Nilai</th></tr>
                @foreach (['knowledge' => ['Knowledge', 'knowledge'], 'skill' => ['Skill', 'skill'], 'character' => ['Character', 'character'], 'social_cohesion' => ['Social Cohesion', 'social']] as $f => [$label, $w])
                    <tr><td>{{ $label }}</td><td class="n">{{ $weights[$w] }}%</td><td class="n">{{ number_format($result->{$f}, 2, ',', '.') }}</td></tr>
                @endforeach
                <tr><th>Nilai akhir</th><th></th><th class="n">{{ number_format($result->final_score, 2, ',', '.') }} ({{ $result->predicate }})</th></tr>
            </table>
            <h2 style="margin-top: 8mm; font-size: 16px">Modul yang ditempuh</h2>
            <div style="font-size: 12px; line-height: 1.6">{{ $skema->modules->pluck('title')->implode(' · ') }}</div>
        @endif
    </div>
</div>
</body>
</html>
