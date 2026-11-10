<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Member;
use App\Services\CertificateIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

/** Sertifikat (PRD 9.7): daftar, unduh, cabut (alasan wajib), terbit ulang (nomor baru), kukuhkan kelulusan inkubasi. */
class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $mine = $request->user()->member?->certificates()->with('result.enrollment.skema')->get() ?? collect();

        return view('dashboard.certificate.index', ['mine' => $mine, 'staff' => $request->user()->can('assessment-view')]);
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('assessment-view'), 403);

        return DataTables::eloquent(Certificate::query()->with('member:id,name,member_number')->select('certificates.*'))
            ->editColumn('issued_at', fn ($c) => $c->issued_at?->translatedFormat('d M Y') ?? 'Diproses')
            ->addColumn('state', fn ($c) => $c->revoked_at ? 'Dicabut' : ($c->issued_at ? 'Valid' : 'Diproses'))
            ->addColumn('action', fn ($c) => view('dashboard.certificate.action', ['certificate' => $c])->render())
            ->rawColumns(['action'])->addIndexColumn()->make(true);
    }

    public function download(Certificate $certificate)
    {
        $this->authorize('download', $certificate);
        abort_unless($certificate->file && Storage::disk('local')->exists($certificate->file), 404, 'Sertifikat sedang diproses.');

        return Storage::disk('local')->download($certificate->file, 'sertifikat-' . Str::slug($certificate->series . '-' . $certificate->member->name) . '.pdf');
    }

    /** Cabut: nomor tetap tercatat dicabut; nilai skema dibuka kuncinya agar bisa diperbaiki. */
    public function revoke(Request $request, Certificate $certificate)
    {
        $this->authorize('revoke', $certificate);
        $reason = $request->validate(['reason' => 'required|string|max:500'], ['reason.required' => 'Alasan pencabutan wajib diisi.'])['reason'];
        abort_if($certificate->revoked_at, 422, 'Sertifikat sudah dicabut.');
        DB::transaction(function () use ($certificate, $reason) {
            $certificate->update(['revoked_at' => now(), 'revoke_reason' => $reason]);
            $certificate->result?->update(['locked_at' => null]);
        });

        return back()->with('success', "Sertifikat {$certificate->number} dicabut. Nilai dapat diperbaiki lalu diterbitkan ulang.");
    }

    /** Terbit ulang = sertifikat baru dengan nomor baru (PRD 9.7). */
    public function reissue(Certificate $certificate, CertificateIssuer $issuer)
    {
        $this->authorize('issue', Certificate::class);
        abort_unless($certificate->revoked_at, 422, 'Hanya sertifikat yang dicabut yang bisa diterbitkan ulang.');
        $new = $certificate->isIncubation() ? $issuer->issueIncubation($certificate->member) : ($certificate->result ? $issuer->issueForResult($certificate->result) : null);
        abort_unless($new, 422, 'Sudah ada sertifikat aktif untuk nilai ini.');

        return back()->with('success', "Sertifikat baru {$new->number} sedang diterbitkan.");
    }

    /** Kukuhkan kelulusan inkubasi: fase magang + perjalanan + sertifikat INK (PRD 9.7). */
    public function graduate(Member $member, CertificateIssuer $issuer)
    {
        $this->authorize('issue', Certificate::class);
        abort_unless($member->passedAllSkemas(), 422, 'Belum semua skema lulus.');
        abort_if($member->certificates()->where('series', Certificate::INCUBATION)->whereNull('revoked_at')->exists(), 422, 'Kelulusan inkubasi sudah dikukuhkan.');
        DB::transaction(function () use ($member) {
            $member->update(['phase' => 'magang']);
            $member->startPhase('magang');
        });
        $certificate = $issuer->issueIncubation($member);

        return back()->with('success', "{$member->name} dikukuhkan lulus inkubasi. Sertifikat {$certificate->number} sedang diterbitkan.");
    }
}
