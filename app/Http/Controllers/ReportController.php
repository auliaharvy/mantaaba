<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Donation;
use App\Models\Journal;
use App\Models\Member;
use App\Models\Setting;
use App\Services\FinancialReports;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Laporan keuangan ISAK 35 (PRD 9.14): tampilan, PDF (kop + penandatangan), CSV, drill-down ke buku besar. */
class ReportController extends Controller
{
    public const REPORTS = [
        'posisi' => 'Laporan Posisi Keuangan', 'aktivitas' => 'Laporan Penghasilan Komprehensif', 'aset-neto' => 'Laporan Perubahan Aset Neto',
        'arus-kas' => 'Laporan Arus Kas', 'pusat-biaya' => 'Laporan per Pusat Biaya', 'piutang' => 'Buku Pembantu Piutang', 'utang' => 'Buku Pembantu Utang',
        'anggaran' => 'Realisasi vs Anggaran', 'rasio' => 'Rasio Keuangan', 'jurnal-umum' => 'Jurnal Umum', 'transparansi' => 'Laporan Transparansi Publik',
    ];

    public function __construct()
    {
        $this->middleware('permission:finance-report-view');
    }

    public function index()
    {
        return view('dashboard.report.index', ['reports' => self::REPORTS]);
    }

    public function show(Request $request, string $report)
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        $data = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date', 'year' => 'nullable|integer|min:2000|max:2100', 'format' => 'nullable|in:pdf,csv']);
        $to = $data['to'] ?? today()->toDateString();
        $from = $data['from'] ?? Carbon::parse($to)->startOfYear()->toDateString();
        $payload = ['report' => $report, 'title' => self::REPORTS[$report], 'from' => $from, 'to' => $to] + $this->build($report, $from, $to, (int) ($data['year'] ?? Carbon::parse($to)->year));

        return match ($data['format'] ?? null) {
            'pdf' => $this->pdf($payload)->download(Str::slug($payload['title']) . "-{$to}.pdf"),
            'csv' => $this->csv($payload),
            default => view('dashboard.report.show', $payload + ['reports' => self::REPORTS]),
        };
    }

    /** Terbitkan Laporan Transparansi ke situs: PDF masuk Dokumen kategori Laporan Transparansi (publik). */
    public function publishTransparency(Request $request)
    {
        abort_unless($request->user()->can('cms-create'), 403);
        $data = $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from']);
        $payload = ['report' => 'transparansi', 'title' => self::REPORTS['transparansi'], 'from' => $data['from'], 'to' => $data['to']] + $this->build('transparansi', $data['from'], $data['to'], Carbon::parse($data['to'])->year);
        $path = 'documents/transparansi-' . $data['from'] . '-' . $data['to'] . '-' . Str::lower(Str::random(6)) . '.pdf';
        Storage::disk('local')->put($path, $this->pdf($payload)->output());
        $title = 'Laporan Transparansi ' . Carbon::parse($data['from'])->translatedFormat('j M Y') . ' – ' . Carbon::parse($data['to'])->translatedFormat('j M Y');
        Document::create(['title' => $title, 'category' => 'Laporan Transparansi', 'visibility' => 'publik', 'file' => $path, 'published_at' => now(),
            'description' => 'Ringkasan donasi masuk, penggunaan dana per program, dan jumlah penerima manfaat.']);

        return back()->with('success', 'Laporan transparansi diterbitkan ke halaman Dokumen situs.');
    }

    private function build(string $report, string $from, string $to, int $year): array
    {
        return match ($report) {
            'posisi' => ['data' => FinancialReports::position($to), 'compare' => FinancialReports::position(Carbon::parse($to)->subYear()->toDateString())],
            'aktivitas' => ['data' => FinancialReports::activities($from, $to)],
            'aset-neto' => ['data' => FinancialReports::netAssetChanges($from, $to)],
            'arus-kas' => ['data' => FinancialReports::cashFlow($from, $to)],
            'pusat-biaya' => ['data' => FinancialReports::byCostCenter($from, $to)],
            'piutang' => ['data' => FinancialReports::subLedger('piutang', $to)],
            'utang' => ['data' => FinancialReports::subLedger('utang', $to)],
            'anggaran' => ['data' => FinancialReports::budgetRealization($year, $to), 'year' => $year],
            'rasio' => ['data' => FinancialReports::ratios($from, $to)],
            'jurnal-umum' => ['data' => Journal::with('lines.account', 'lines.costCenter')->whereIn('status', Journal::POSTED)->whereBetween('date', [$from, $to])->orderBy('date')->orderBy('number')->get()],
            'transparansi' => ['data' => self::transparency($from, $to)],
        };
    }

    /** Ringkasan untuk publik: tanpa nama donatur (PRD 9.14, 9.15). */
    public static function transparency(string $from, string $to): array
    {
        $donations = Donation::where('status', 'terverifikasi')->whereBetween('transferred_at', [$from, $to])->whereHas('journal', fn ($q) => $q->whereIn('status', Journal::POSTED))
            ->get()->groupBy('purpose')->map(fn ($g) => ['count' => $g->count(), 'amount' => $g->sum(fn ($d) => Money::cents($d->amount))]);
        $activity = FinancialReports::activities($from, $to);

        return [
            'donations' => $donations, 'donation_total' => $donations->sum('amount'),
            'revenue_total' => $activity['revenueTanpa'] + $activity['revenueDengan'] - $activity['released'],
            'usage' => $activity['expenses']->groupBy(fn ($r) => $r['account']->statement_group ?: 'Lainnya')->map(fn ($g) => $g->sum('amount'))->filter(),
            'usage_detail' => $activity['expenses']->filter(fn ($r) => $r['amount'] !== 0)->map(fn ($r) => ['name' => $r['account']->name, 'amount' => $r['amount']])->values(),
            'expense_total' => $activity['expenseTotal'],
            'beneficiaries' => Member::where('category', 'siswa')->where('status', 'aktif')->count(),
        ];
    }

    private function pdf(array $payload)
    {
        return Pdf::loadView('pdf.report', $payload + ['site' => Setting::current()])->setPaper('a4');
    }

    private function csv(array $p)
    {
        $rows = self::csvRows($p);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, Str::slug($p['title']) . "-{$p['to']}.csv", ['Content-Type' => 'text/csv']);
    }

    /** Baris CSV (angka dalam rupiah, desimal titik). */
    public static function csvRows(array $p): array
    {
        $rp = fn (int $cents) => Money::decimal($cents);
        $d = $p['data'];
        $rows = [];

        switch ($p['report']) {
            case 'posisi':
                $rows[] = ['pos', 'kode', 'akun', 'jumlah'];
                foreach (['current' => 'Aset lancar', 'nonCurrent' => 'Aset tidak lancar', 'liabilities' => 'Liabilitas', 'unrestricted' => 'Aset neto tanpa pembatasan', 'restricted' => 'Aset neto dengan pembatasan'] as $key => $label) {
                    foreach ($d[$key] as $r) {
                        $rows[] = [$label, $r['account']->code, $r['account']->name, $rp($r['amount'])];
                    }
                }
                $rows[] = ['Aset neto tanpa pembatasan', '', 'Surplus berjalan', $rp($d['surplus']['tanpa'])];
                $rows[] = ['Aset neto dengan pembatasan', '', 'Surplus berjalan', $rp($d['surplus']['dengan'])];
                $rows[] = ['Total', '', 'Aset', $rp($d['totals']['assets'])];
                $rows[] = ['Total', '', 'Liabilitas + aset neto', $rp($d['totals']['liabilities_net_assets'])];
                break;
            case 'aktivitas':
                $rows[] = ['kelompok', 'kode', 'akun', 'tanpa_pembatasan', 'dengan_pembatasan'];
                foreach ($d['revenues'] as $r) {
                    $rows[] = ['Pendapatan', $r['account']->code, $r['account']->name, $r['restricted'] ? '' : $rp($r['amount']), $r['restricted'] ? $rp($r['amount']) : ''];
                }
                $rows[] = ['Pembebasan', '', 'Aset neto dibebaskan dari pembatasan', '', $rp(-$d['released'])];
                foreach ($d['expenses'] as $r) {
                    $rows[] = ['Beban', $r['account']->code, $r['account']->name, $rp($r['amount']), ''];
                }
                $rows[] = ['Surplus/defisit', '', '', $rp($d['surplusTanpa']), $rp($d['surplusDengan'])];
                break;
            case 'aset-neto':
                $rows[] = ['kelompok', 'saldo_awal', 'surplus', 'lainnya', 'saldo_akhir'];
                foreach (['tanpa', 'dengan'] as $k) {
                    $rows[] = [$k, $rp($d[$k]['opening']), $rp($d[$k]['surplus']), $rp($d[$k]['other']), $rp($d[$k]['closing'])];
                }
                break;
            case 'arus-kas':
                $rows[] = ['kelompok', 'pos', 'jumlah'];
                foreach ($d['groups'] as $group => $items) {
                    foreach ($items as $label => $amount) {
                        $rows[] = [$group, $label, $rp($amount)];
                    }
                }
                $rows[] = ['', 'Saldo awal kas', $rp($d['opening'])];
                $rows[] = ['', 'Arus kas bersih', $rp($d['net'])];
                $rows[] = ['', 'Saldo akhir kas', $rp($d['closing'])];
                break;
            case 'pusat-biaya':
                $rows[] = ['kode', 'pusat_biaya', 'pendapatan', 'beban', 'bersih'];
                foreach ($d as $r) {
                    $rows[] = [$r['center']->code, $r['center']->name, $rp($r['revenue']), $rp($r['expense']), $rp($r['net'])];
                }
                break;
            case 'piutang':
            case 'utang':
                $rows[] = ['pihak', 'saldo', '0-30', '31-60', '61-90', '>90'];
                foreach ($d as $r) {
                    $rows[] = [$r['party']->name, $rp($r['balance']), ...array_map($rp, array_values($r['aging']))];
                }
                break;
            case 'anggaran':
                $rows[] = ['akun', 'pusat_biaya', 'anggaran', 'realisasi', 'persen'];
                foreach ($d as $r) {
                    $rows[] = [$r['budget']->account->label(), $r['budget']->costCenter->code, $rp($r['planned']), $rp($r['actual']), $r['percent']];
                }
                break;
            case 'rasio':
                $rows[] = ['rasio', 'nilai'];
                $rows[] = ['Beban program / total beban (%)', $d['program_ratio']];
                $rows[] = ['Biaya per siswa per bulan', $d['cost_per_student'] !== null ? $rp($d['cost_per_student']) : ''];
                $rows[] = ['Rasio lancar', $d['current_ratio']];
                foreach ($d['sources'] as $label => $s) {
                    $rows[] = ["Sumber pendapatan: {$label} (%)", $s['percent']];
                }
                break;
            case 'jurnal-umum':
                $rows[] = ['tanggal', 'nomor', 'uraian', 'akun', 'debit', 'kredit'];
                foreach ($d as $j) {
                    foreach ($j->lines as $l) {
                        $rows[] = [$j->date->toDateString(), $j->number, $j->description, $l->account->label(), $l->debit, $l->credit];
                    }
                }
                break;
            case 'transparansi':
                $rows[] = ['pos', 'jumlah'];
                foreach ($d['donations'] as $purpose => $v) {
                    $rows[] = ['Donasi ' . (Donation::PURPOSES[$purpose][0] ?? $purpose), $rp($v['amount'])];
                }
                foreach ($d['usage'] as $group => $amount) {
                    $rows[] = ["Penggunaan: {$group}", $rp($amount)];
                }
                $rows[] = ['Penerima manfaat (siswa aktif)', $d['beneficiaries']];
                break;
        }

        return $rows;
    }
}
