<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\MemberImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:member-create');
    }

    public function create()
    {
        return view('dashboard.membership.import');
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, MembershipController::EXPORT_COLUMNS);
            fputcsv($out, ['Budi Santoso', 'budi@contoh.id', '081234567890', 'AKP2I-0001', 'KIP-123', 'utama', 'JABAR-BDG', 'B', 'A,B', '12.345.678.9-012.000', '2020-01-15']);
            fclose($out);
        }, 'template-import-anggota.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, MemberImporter $importer)
    {
        $this->authorize('create', Member::class);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        set_time_limit(300);
        $result = $importer->import($request->file('file')->getRealPath(), ! $request->boolean('send_later'));

        $report = null;
        if ($result['failures']) {
            $report = Str::uuid() . '.csv';
            $out = fopen('php://temp', 'w+');
            fputcsv($out, ['baris', 'alasan', ...MembershipController::EXPORT_COLUMNS]);
            foreach ($result['failures'] as $failure) {
                fputcsv($out, [$failure['line'], $failure['reason'], ...array_map(fn ($c) => $failure['row'][$c] ?? '', MembershipController::EXPORT_COLUMNS)]);
            }
            rewind($out);
            Storage::disk('local')->put("imports/{$report}", stream_get_contents($out));
            fclose($out);
        }

        return redirect()->route('membership.import.form')->with([
            'success' => "{$result['imported']} anggota berhasil diimpor." . ($request->boolean('send_later') ? ' Email aktivasi belum dikirim.' : ''),
            'import_failures' => array_slice($result['failures'], 0, 100),
            'import_failure_count' => count($result['failures']),
            'import_report' => $report,
        ]);
    }

    public function failures(string $report)
    {
        abort_unless(preg_match('/^[0-9a-f-]{36}\.csv$/', $report) && Storage::disk('local')->exists("imports/{$report}"), 404);

        return Storage::disk('local')->download("imports/{$report}", 'baris-gagal-import-anggota.csv');
    }
}
