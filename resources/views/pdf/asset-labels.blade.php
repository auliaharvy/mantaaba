<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 10mm; }
    body { font-family: 'Helvetica', sans-serif; margin: 0; }
    .label { display: inline-block; width: 60mm; height: 40mm; border: 1px dashed #999; margin: 0 2mm 3mm 0; padding: 3mm; box-sizing: border-box; vertical-align: top; overflow: hidden; }
    .label img.qr { width: 26mm; height: 26mm; float: left; margin-right: 3mm; }
    .code { font-size: 11px; font-weight: bold; color: #5B3A1F; }
    .name { font-size: 9px; margin-top: 2px; }
    .site { font-size: 7px; color: #6e5b4b; position: absolute; }
</style>
</head>
<body>
    @foreach ($labels as $l)
        <div class="label">
            <img class="qr" src="{{ $l['qr'] }}" alt="">
            <div class="code">{{ $l['asset']->code }}</div>
            <div class="name">{{ \Illuminate\Support\Str::limit($l['asset']->name, 40) }}</div>
            @if ($l['asset']->vehicle?->plate_number)<div class="name">{{ $l['asset']->vehicle->plate_number }}</div>@endif
            <div style="font-size: 7px; color: #6e5b4b; margin-top: 4px">{{ $site->site_name }}<br>Pindai untuk detail aset</div>
        </div>
    @endforeach
</body>
</html>
