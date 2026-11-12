{{-- Pemetaan Standar Kompetensi (format dokumen skema Rumah Mantaaba): Tujuan Utama → Fungsi Kunci → Fungsi Utama → Fungsi Dasar/Unit. --}}
@php
    $groups = $skema->functionGroups();
    $total = $skema->units->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 14mm 12mm; }
    body { font-family: 'Helvetica', sans-serif; font-size: 8.5px; color: #1f2937; }
    table { width: 100%; border-collapse: collapse; }
    .head td { padding: 5px 7px; background: #eef2f6; }
    .head td.label { width: 70px; background: #1b7f80; color: #fff; font-weight: bold; }
    .head td.value { font-weight: bold; font-size: 9.5px; }
    .bar { margin: 12px 0 10px; background: #183447; color: #fff; font-weight: bold; padding: 7px; font-size: 9.5px; }
    .map th { background: #1b7f80; color: #fff; padding: 7px 5px; font-size: 9px; text-align: center; border: 1px solid #fff; }
    .map td { padding: 7px 6px; vertical-align: middle; border-bottom: 1px solid #ffffff; }
    .map td.purpose { background: #e6f2f2; font-weight: bold; font-size: 9.5px; width: 26%; }
    .map td.key { background: #e6f2f2; width: 22%; }
    .map td.main { background: #eef2f6; width: 20%; }
    .map td.unit { background: #f6f8fa; }
    .map tr.last td.main, .map tr.last td.unit { background: #fdf1e3; font-weight: bold; }
    .unit-code { white-space: nowrap; }
    .foot { margin-top: 10px; font-size: 7.5px; color: #6b7280; }
</style>
</head>
<body>
    <table class="head">
        <tr><td class="label">Lembaga</td><td class="value">{{ $site->site_name }}</td></tr>
        <tr><td class="label">Skema</td><td class="value">{{ $skema->title ?: $skema->name }}</td></tr>
    </table>
    <div class="bar">A. PEMETAAN STANDAR KOMPETENSI — KERANGKA INTERNAL MANTAABA</div>
    <table class="map">
        <thead><tr><th>Tujuan Utama</th><th>Fungsi Kunci</th><th>Fungsi Utama</th><th>Fungsi Dasar / Unit Kompetensi</th></tr></thead>
        <tbody>
            @foreach ($groups as $g => [$keyFunction, $units])
                @foreach ($units as $i => $unit)
                    <tr class="{{ $loop->last ? 'last' : '' }}">
                        @if ($g === 0 && $i === 0)<td class="purpose" rowspan="{{ $total }}">{{ strip_tags($skema->objectives ?: $skema->description) }}</td>@endif
                        @if ($i === 0)<td class="key" rowspan="{{ $units->count() }}">{{ $keyFunction ?: '-' }}</td>@endif
                        <td class="main">{{ $unit->main_function ?: '-' }}</td>
                        <td class="unit"><span class="unit-code">{{ $unit->code }}</span> &nbsp;|&nbsp; {{ $unit->title }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    <p class="foot">{{ $skema->code }} · {{ $total }} unit kompetensi · {{ $skema->modules->count() }} elemen · {{ $skema->criteriaCount() }} KUK · dicetak {{ now()->translatedFormat('j F Y') }}</p>
</body>
</html>
