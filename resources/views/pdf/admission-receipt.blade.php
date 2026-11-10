{{-- Bukti pendaftaran (PRD 9.3), A4 dompdf. --}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'Helvetica', sans-serif; color: #2b1d12; font-size: 12px; }
    .kop { border-bottom: 2px solid #C9A04A; padding-bottom: 8px; margin-bottom: 18px; }
    .kop img { height: 56px; vertical-align: middle; }
    .kop .name { display: inline-block; vertical-align: middle; margin-left: 10px; }
    .kop .name b { font-size: 16px; color: #5B3A1F; letter-spacing: 1px; }
    h1 { font-size: 18px; color: #5B3A1F; margin: 0 0 4px; }
    .number { font-size: 22px; font-weight: bold; color: #5B3A1F; letter-spacing: 2px; margin: 10px 0 16px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 5px 4px; border-bottom: 1px solid #e8dccb; vertical-align: top; }
    td.l { width: 35%; color: #6e5b4b; }
    .note { margin-top: 18px; padding: 10px; background: #F7F1E6; }
</style>
</head>
<body>
    <div class="kop">
        @php($logo = public_path('media/logos/logo-mantaaba-512.jpg'))
        <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents($logo)) }}" alt="">
        <span class="name"><b>{{ strtoupper($site->site_name) }}</b><br>{{ $site->foundation_name }}{{ $site->fullAddress() ? ' · ' . $site->fullAddress() : '' }}</span>
    </div>
    <h1>Bukti Pendaftaran Siswa</h1>
    <div>{{ $applicant->batch->name }}</div>
    <div class="number">{{ $applicant->registration_number }}</div>
    <table>
        <tr><td class="l">Nama</td><td>{{ $applicant->name }}</td></tr>
        <tr><td class="l">Tempat, tanggal lahir</td><td>{{ $applicant->birth_place }}, {{ $applicant->birth_date->translatedFormat('j F Y') }}</td></tr>
        <tr><td class="l">Asal</td><td>{{ $applicant->village }}, {{ $applicant->district }}, {{ $applicant->regency }}</td></tr>
        <tr><td class="l">Tanggal daftar</td><td>{{ $applicant->created_at->translatedFormat('j F Y H:i') }}</td></tr>
        <tr><td class="l">Berkas diunggah</td><td>{{ collect(App\Models\Applicant::DOCUMENTS)->only(array_keys($applicant->documents))->map(fn ($d) => $d[0])->implode(', ') }}</td></tr>
    </table>
    <div class="note">
        Simpan bukti ini. Cek status pendaftaran di {{ route('site.admission.status') }} dengan nomor pendaftaran dan tanggal lahir.
        Panitia akan menghubungi melalui WhatsApp {{ $applicant->phone }}.
    </div>
</body>
</html>
