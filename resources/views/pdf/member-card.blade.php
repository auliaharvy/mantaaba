{{-- Kartu anggota 85,6 × 54 mm (dompdf). --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'Helvetica', sans-serif; color: #2b1d12; }
    .card { position: relative; width: 85.6mm; height: 54mm; background: #F7F1E6; }
    .top { background: #5B3A1F; color: #fff; padding: 2.2mm 3mm; border-bottom: 0.8mm solid #C9A04A; }
    .top img { height: 8mm; vertical-align: middle; }
    .top span { vertical-align: middle; font-size: 7.5pt; font-weight: bold; letter-spacing: 0.5pt; margin-left: 1.5mm; }
    .photo { position: absolute; left: 3mm; top: 15mm; width: 19mm; height: 24mm; background: #e8dccb; }
    .photo img { width: 19mm; height: 24mm; }
    .info { position: absolute; left: 25mm; top: 15mm; right: 22mm; font-size: 7pt; }
    .name { font-size: 9pt; font-weight: bold; color: #5B3A1F; margin-bottom: 1mm; }
    .number { font-size: 8pt; font-weight: bold; letter-spacing: 0.5pt; }
    .qr { position: absolute; right: 3mm; top: 16mm; width: 17mm; }
    .qr img { width: 17mm; height: 17mm; }
    .foot { position: absolute; bottom: 2mm; left: 3mm; right: 3mm; font-size: 5.5pt; color: #6e5b4b; }
</style>
</head>
<body>
<div class="card">
    <div class="top"><img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('media/logos/logo-mantaaba-512.jpg'))) }}" alt=""><span>{{ strtoupper($site->site_name) }}</span></div>
    <div class="photo">@if ($photo)<img src="{{ $photo }}" alt="">@endif</div>
    <div class="info">
        <div class="name">{{ $member->name }}</div>
        <div class="number">{{ $member->member_number }}</div>
        <div>{{ App\Models\Member::CATEGORIES[$member->category] }}{{ $member->batch ? ' · Angkatan ' . $member->batch->code : '' }}</div>
        <div>Bergabung {{ $member->joined_at?->translatedFormat('F Y') ?? '-' }}</div>
    </div>
    <div class="qr"><img src="{{ $qr }}" alt=""></div>
    <div class="foot">{{ $site->foundation_name }} · Kartu ini milik {{ $site->site_name }}. Pindai QR untuk verifikasi.</div>
</div>
</body>
</html>
