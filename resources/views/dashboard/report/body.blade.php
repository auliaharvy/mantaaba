{{-- Isi laporan (dipakai halaman & PDF). Angka dalam sen → rupiah. $links: tautan drill-down ke buku besar. --}}
@php
    $rp = fn ($cents) => ($cents < 0 ? '(' : '') . number_format(abs($cents) / 100, 0, ',', '.') . ($cents < 0 ? ')' : '');
    $ledger = fn ($account, $label) => ($links ?? false) ? '<a href="' . e(route('ledger.index', ['account' => $account->id, 'from' => $from, 'to' => $to])) . '">' . e($label) . '</a>' : e($label);
    $d = $data;
    $period = in_array($report, ['posisi', 'piutang', 'utang']) ? 'Per ' . \Illuminate\Support\Carbon::parse($to)->translatedFormat('j F Y') : \Illuminate\Support\Carbon::parse($from)->translatedFormat('j F Y') . ' – ' . \Illuminate\Support\Carbon::parse($to)->translatedFormat('j F Y');
@endphp
<p class="text-muted">{{ $report === 'anggaran' ? 'Tahun ' . $year : $period }}</p>
@switch($report)
    @case('posisi')
        <table>
            <tr><th>ASET</th><th class="n">{{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</th><th class="n">{{ \Illuminate\Support\Carbon::parse($to)->subYear()->format('d/m/Y') }}</th></tr>
            @foreach (['current' => 'Aset lancar', 'nonCurrent' => 'Aset tidak lancar'] as $key => $label)
                <tr><td colspan="3"><b>{{ $label }}</b></td></tr>
                @foreach ($d[$key] as $i => $r)
                    @continue(! $r['amount'] && ! ($compare[$key][$i]['amount'] ?? 0))
                    <tr><td class="sub">{!! $ledger($r['account'], $r['account']->name) !!}</td><td class="n">{{ $rp($r['amount']) }}</td><td class="n">{{ $rp($compare[$key][$i]['amount'] ?? 0) }}</td></tr>
                @endforeach
                <tr class="total"><td>Jumlah {{ Str::lower($label) }}</td><td class="n">{{ $rp($d['totals'][$key === 'current' ? 'current' : 'non_current']) }}</td><td class="n">{{ $rp($compare['totals'][$key === 'current' ? 'current' : 'non_current']) }}</td></tr>
            @endforeach
            <tr class="total"><td>JUMLAH ASET</td><td class="n">{{ $rp($d['totals']['assets']) }}</td><td class="n">{{ $rp($compare['totals']['assets']) }}</td></tr>
            <tr><th colspan="3">LIABILITAS & ASET NETO</th></tr>
            @foreach ($d['liabilities'] as $i => $r)
                @continue(! $r['amount'] && ! ($compare['liabilities'][$i]['amount'] ?? 0))
                <tr><td class="sub">{!! $ledger($r['account'], $r['account']->name) !!}</td><td class="n">{{ $rp($r['amount']) }}</td><td class="n">{{ $rp($compare['liabilities'][$i]['amount'] ?? 0) }}</td></tr>
            @endforeach
            <tr class="total"><td>Jumlah liabilitas</td><td class="n">{{ $rp($d['totals']['liabilities']) }}</td><td class="n">{{ $rp($compare['totals']['liabilities']) }}</td></tr>
            @foreach (['unrestricted' => ['Aset neto tanpa pembatasan', 'tanpa'], 'restricted' => ['Aset neto dengan pembatasan', 'dengan']] as $key => [$label, $s])
                <tr><td colspan="3"><b>{{ $label }}</b></td></tr>
                @foreach ($d[$key] as $i => $r)
                    @continue(! $r['amount'] && ! ($compare[$key][$i]['amount'] ?? 0))
                    <tr><td class="sub">{!! $ledger($r['account'], $r['account']->name) !!}</td><td class="n">{{ $rp($r['amount']) }}</td><td class="n">{{ $rp($compare[$key][$i]['amount'] ?? 0) }}</td></tr>
                @endforeach
                <tr><td class="sub">Surplus/defisit belum ditutup</td><td class="n">{{ $rp($d['surplus'][$s]) }}</td><td class="n">{{ $rp($compare['surplus'][$s]) }}</td></tr>
                <tr class="total"><td>Jumlah {{ Str::lower($label) }}</td><td class="n">{{ $rp($d['totals'][$key]) }}</td><td class="n">{{ $rp($compare['totals'][$key]) }}</td></tr>
            @endforeach
            <tr class="total"><td>JUMLAH LIABILITAS & ASET NETO</td><td class="n">{{ $rp($d['totals']['liabilities_net_assets']) }}</td><td class="n">{{ $rp($compare['totals']['liabilities_net_assets']) }}</td></tr>
        </table>
        @if ($d['totals']['assets'] !== $d['totals']['liabilities_net_assets'])<p style="color: #b91c1c"><b>Peringatan: aset tidak sama dengan liabilitas + aset neto.</b></p>@endif
        @break

    @case('aktivitas')
        <table>
            <tr><th></th><th class="n">Tanpa pembatasan</th><th class="n">Dengan pembatasan</th><th class="n">Jumlah</th></tr>
            <tr><td colspan="4"><b>Pendapatan</b></td></tr>
            @foreach ($d['revenues'] as $r)
                @continue(! $r['amount'] || $r['account']->code === '4-9000')
                <tr><td class="sub">{!! $ledger($r['account'], $r['account']->name) !!}</td><td class="n">{{ $r['restricted'] ? '' : $rp($r['amount']) }}</td><td class="n">{{ $r['restricted'] ? $rp($r['amount']) : '' }}</td><td class="n">{{ $rp($r['amount']) }}</td></tr>
            @endforeach
            <tr><td class="sub">Aset neto dibebaskan dari pembatasan</td><td class="n">{{ $rp($d['released']) }}</td><td class="n">{{ $rp(-$d['released']) }}</td><td class="n">0</td></tr>
            <tr class="total"><td>Jumlah pendapatan</td><td class="n">{{ $rp($d['revenueTanpa']) }}</td><td class="n">{{ $rp($d['revenueDengan'] - $d['released']) }}</td><td class="n">{{ $rp($d['revenueTanpa'] + $d['revenueDengan'] - $d['released']) }}</td></tr>
            <tr><td colspan="4"><b>Beban</b></td></tr>
            @foreach ($d['expenses'] as $r)
                @continue(! $r['amount'])
                <tr><td class="sub">{!! $ledger($r['account'], $r['account']->name) !!}</td><td class="n">{{ $rp($r['amount']) }}</td><td class="n"></td><td class="n">{{ $rp($r['amount']) }}</td></tr>
            @endforeach
            <tr class="total"><td>Jumlah beban</td><td class="n">{{ $rp($d['expenseTotal']) }}</td><td class="n">0</td><td class="n">{{ $rp($d['expenseTotal']) }}</td></tr>
            <tr class="total"><td>SURPLUS (DEFISIT)</td><td class="n">{{ $rp($d['surplusTanpa']) }}</td><td class="n">{{ $rp($d['surplusDengan']) }}</td><td class="n">{{ $rp($d['surplusTanpa'] + $d['surplusDengan']) }}</td></tr>
            <tr><td>Penghasilan komprehensif lain</td><td class="n">0</td><td class="n">0</td><td class="n">0</td></tr>
            <tr class="total"><td>TOTAL PENGHASILAN KOMPREHENSIF</td><td class="n">{{ $rp($d['surplusTanpa']) }}</td><td class="n">{{ $rp($d['surplusDengan']) }}</td><td class="n">{{ $rp($d['surplusTanpa'] + $d['surplusDengan']) }}</td></tr>
        </table>
        @break

    @case('aset-neto')
        <table>
            <tr><th></th><th class="n">Tanpa pembatasan</th><th class="n">Dengan pembatasan</th><th class="n">Jumlah</th></tr>
            @foreach (['opening' => 'Saldo awal', 'surplus' => 'Surplus (defisit) periode berjalan', 'other' => 'Perubahan langsung aset neto (saldo awal, koreksi)', 'closing' => 'Saldo akhir'] as $k => $label)
                <tr class="{{ in_array($k, ['opening', 'closing']) ? 'total' : '' }}"><td>{{ $label }}</td><td class="n">{{ $rp($d['tanpa'][$k]) }}</td><td class="n">{{ $rp($d['dengan'][$k]) }}</td><td class="n">{{ $rp($d['tanpa'][$k] + $d['dengan'][$k]) }}</td></tr>
            @endforeach
        </table>
        @break

    @case('arus-kas')
        <table>
            @foreach (['operasi' => 'Arus kas dari aktivitas operasi', 'investasi' => 'Arus kas dari aktivitas investasi', 'pendanaan' => 'Arus kas dari aktivitas pendanaan'] as $g => $label)
                <tr><td colspan="2"><b>{{ $label }}</b></td></tr>
                @forelse ($d['groups'][$g] as $item => $amount)
                    <tr><td class="sub">{{ $amount >= 0 ? 'Penerimaan' : 'Pembayaran' }}: {{ $item }}</td><td class="n">{{ $rp($amount) }}</td></tr>
                @empty
                    <tr><td class="sub">—</td><td class="n">0</td></tr>
                @endforelse
                <tr class="total"><td>Kas bersih {{ $g }}</td><td class="n">{{ $rp($d['totals'][$g]) }}</td></tr>
            @endforeach
            <tr class="total"><td>KENAIKAN (PENURUNAN) BERSIH KAS</td><td class="n">{{ $rp($d['net']) }}</td></tr>
            <tr><td>Kas dan setara kas awal periode</td><td class="n">{{ $rp($d['opening']) }}</td></tr>
            <tr class="total"><td>KAS DAN SETARA KAS AKHIR PERIODE</td><td class="n">{{ $rp($d['closing']) }}</td></tr>
        </table>
        @if ($d['opening'] + $d['net'] !== $d['closing'])<p style="color: #b91c1c"><b>Peringatan: saldo awal + arus bersih ≠ saldo akhir.</b></p>@endif
        @break

    @case('pusat-biaya')
        <table>
            <tr><th>Pusat biaya</th><th class="n">Pendapatan</th><th class="n">Beban</th><th class="n">Bersih</th></tr>
            @foreach ($d as $r)<tr><td>{{ $r['center']->code }} · {{ $r['center']->name }}</td><td class="n">{{ $rp($r['revenue']) }}</td><td class="n">{{ $rp($r['expense']) }}</td><td class="n">{{ $rp($r['net']) }}</td></tr>@endforeach
            <tr class="total"><td>Jumlah</td><td class="n">{{ $rp($d->sum('revenue')) }}</td><td class="n">{{ $rp($d->sum('expense')) }}</td><td class="n">{{ $rp($d->sum('net')) }}</td></tr>
        </table>
        @break

    @case('piutang')
    @case('utang')
        <table>
            <tr><th>Pihak</th><th class="n">Saldo</th><th class="n">0–30 hari</th><th class="n">31–60</th><th class="n">61–90</th><th class="n">&gt; 90</th></tr>
            @forelse ($d as $r)
                <tr><td>{{ $r['party']->name }}</td><td class="n">{{ $rp($r['balance']) }}</td>@foreach ($r['aging'] as $v)<td class="n">{{ $rp($v) }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="6">Tidak ada saldo {{ $report }}.</td></tr>
            @endforelse
            <tr class="total"><td>Jumlah</td><td class="n">{{ $rp($d->sum('balance')) }}</td>@foreach (['0-30', '31-60', '61-90', '>90'] as $b)<td class="n">{{ $rp($d->sum(fn ($r) => $r['aging'][$b])) }}</td>@endforeach</tr>
        </table>
        @break

    @case('anggaran')
        <table>
            <tr><th>Akun</th><th>Pusat biaya</th><th class="n">Anggaran</th><th class="n">Realisasi</th><th class="n">Serapan</th></tr>
            @forelse ($d as $r)
                <tr><td>{{ $r['budget']->account->label() }}</td><td>{{ $r['budget']->costCenter->code }}</td><td class="n">{{ $rp($r['planned']) }}</td><td class="n">{{ $rp($r['actual']) }}</td>
                    <td class="n" style="{{ ($r['percent'] ?? 0) > 100 ? 'color: #b91c1c; font-weight: 700' : '' }}">{{ $r['percent'] !== null ? $r['percent'] . '%' : '-' }}</td></tr>
            @empty
                <tr><td colspan="5">Belum ada anggaran untuk tahun ini.</td></tr>
            @endforelse
        </table>
        @break

    @case('rasio')
        <table>
            <tr><td>Rasio beban program terhadap total beban</td><td class="n">{{ $d['program_ratio'] !== null ? $d['program_ratio'] . '%' : '-' }}</td></tr>
            <tr><td>Biaya per siswa per bulan ({{ $d['students'] }} siswa aktif, {{ $d['months'] }} bulan)</td><td class="n">{{ $d['cost_per_student'] !== null ? 'Rp ' . $rp($d['cost_per_student']) : '-' }}</td></tr>
            <tr><td>Rasio lancar (aset lancar ÷ liabilitas)</td><td class="n">{{ $d['current_ratio'] ?? '-' }}</td></tr>
            <tr><td colspan="2"><b>Sumber pendapatan</b></td></tr>
            @forelse ($d['sources'] as $label => $s)<tr><td class="sub">{{ $label }}</td><td class="n">{{ $s['percent'] }}% (Rp {{ $rp($s['amount']) }})</td></tr>@empty<tr><td class="sub">—</td><td></td></tr>@endforelse
        </table>
        @break

    @case('jurnal-umum')
        <table>
            <tr><th>Tanggal</th><th>Nomor</th><th>Akun</th><th class="n">Debit</th><th class="n">Kredit</th></tr>
            @forelse ($d as $j)
                <tr><td>{{ $j->date->format('d/m/Y') }}</td><td>@if ($links ?? false)<a href="{{ route('journal.show', $j) }}">{{ $j->number }}</a>@else{{ $j->number }}@endif</td><td colspan="3"><i>{{ $j->description }}</i></td></tr>
                @foreach ($j->lines as $l)<tr><td></td><td></td><td class="sub">{{ $l->account->label() }}</td><td class="n">{{ (float) $l->debit ? number_format($l->debit, 0, ',', '.') : '' }}</td><td class="n">{{ (float) $l->credit ? number_format($l->credit, 0, ',', '.') : '' }}</td></tr>@endforeach
            @empty
                <tr><td colspan="5">Tidak ada voucher terposting.</td></tr>
            @endforelse
        </table>
        @break

    @case('transparansi')
        <table>
            <tr><td colspan="2"><b>Donasi masuk (terverifikasi & tercatat)</b></td></tr>
            @forelse ($d['donations'] as $purpose => $v)<tr><td class="sub">{{ App\Models\Donation::PURPOSES[$purpose][0] ?? $purpose }} ({{ $v['count'] }} donasi)</td><td class="n">{{ $rp($v['amount']) }}</td></tr>@empty<tr><td class="sub">—</td><td class="n">0</td></tr>@endforelse
            <tr class="total"><td>Jumlah donasi</td><td class="n">{{ $rp($d['donation_total']) }}</td></tr>
            <tr><td>Total pendapatan (termasuk usaha kebun/kolam & lainnya)</td><td class="n">{{ $rp($d['revenue_total']) }}</td></tr>
            <tr><td colspan="2"><b>Penggunaan dana per program</b></td></tr>
            @foreach ($d['usage'] as $group => $amount)<tr><td class="sub">{{ $group }}</td><td class="n">{{ $rp($amount) }}</td></tr>@endforeach
            <tr class="total"><td>Jumlah penggunaan</td><td class="n">{{ $rp($d['expense_total']) }}</td></tr>
            <tr><td>Penerima manfaat (siswa aktif)</td><td class="n">{{ $d['beneficiaries'] }} orang</td></tr>
        </table>
        <p style="font-size: 11px; color: #6e5b4b">Nama donatur tidak dicantumkan untuk menjaga privasi. Rincian lengkap tersedia bagi pengurus dan auditor.</p>
        @break
@endswitch
