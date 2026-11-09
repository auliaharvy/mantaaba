{{-- Sertifikat A4 landscape (dompdf). Q3: format & penandatangan per event. --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'Helvetica', sans-serif; color: #002d14; }
    .page { position: relative; width: 297mm; height: 210mm; }
    .bg { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
    .frame { position: absolute; top: 10mm; left: 10mm; right: 10mm; bottom: 10mm; border: 3px solid #00923f; }
    .content { position: absolute; top: 28mm; left: 25mm; right: 25mm; text-align: center; }
    .title { font-size: 40px; font-weight: bold; letter-spacing: 6px; color: #00662c; margin: 0; }
    .number { font-size: 13px; margin-top: 4px; }
    .given { font-size: 15px; margin-top: 14mm; }
    .name { font-size: 32px; font-weight: bold; margin: 4mm 0 2mm; border-bottom: 1px solid #00923f; display: inline-block; padding: 0 10mm 2mm; }
    .as { font-size: 15px; }
    .event { font-size: 20px; font-weight: bold; margin-top: 3mm; }
    .meta { font-size: 13px; margin-top: 3mm; }
    .signs { position: absolute; bottom: 22mm; left: 25mm; right: 60mm; }
    .sign { display: inline-block; width: 45%; text-align: center; vertical-align: bottom; font-size: 13px; }
    .sign img { height: 18mm; }
    .sign .who { font-weight: bold; border-top: 1px solid #002d14; padding-top: 1mm; margin: 0 8mm; }
    .qr { position: absolute; bottom: 18mm; right: 22mm; width: 32mm; text-align: center; font-size: 8px; }
    .qr img { width: 30mm; height: 30mm; }
</style>
</head>
<body>
<div class="page">
    @if ($background)<img class="bg" src="{{ $background }}" alt="">@else<div class="frame"></div>@endif
    <div class="content">
        <p class="title">SERTIFIKAT</p>
        <div class="number">Nomor: {{ $certificate->number }}</div>
        <div class="given">diberikan kepada</div>
        <div class="name">{{ $participant->user->name }}</div>
        <div class="as">sebagai Peserta</div>
        <div class="event">{{ $event->title }}</div>
        <div class="meta">
            {{ $event->starts_at?->translatedFormat('j F Y') }}{{ $event->ends_at && ! $event->ends_at->isSameDay($event->starts_at) ? ' – ' . $event->ends_at->translatedFormat('j F Y') : '' }}
            · {{ App\Models\Event::TYPES[$event->type] }}
            @if ($event->skp_hours) · {{ rtrim(rtrim(number_format($event->skp_hours, 2, ',', '.'), '0'), ',') }} jam SKP @endif
        </div>
        <div class="meta">Diselenggarakan oleh {{ $event->organization?->name ?? config('app.name') }}</div>
    </div>
    <div class="signs">
        @foreach ($signatories as $s)
            <div class="sign">
                @if ($s['signature_uri'])<img src="{{ $s['signature_uri'] }}" alt="">@else<div style="height: 18mm"></div>@endif
                <div class="who">{{ $s['name'] }}</div>
                <div>{{ $s['title'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
    <div class="qr">
        <img src="{{ $qr }}" alt="">
        <div>Verifikasi: {{ $verifyUrl }}</div>
    </div>
</div>
</body>
</html>
