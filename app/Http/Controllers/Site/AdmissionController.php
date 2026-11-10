<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewApplicant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Pendaftaran siswa publik (PRD 9.3): formulir 6 langkah, bukti pendaftaran, cek status. */
class AdmissionController extends Controller
{
    /** Field per langkah wizard, untuk membuka langkah pertama yang bermasalah setelah validasi gagal. */
    public const STEPS = [
        'Data diri' => ['name', 'nik', 'birth_place', 'birth_date', 'gender', 'phone', 'email', 'address', 'village', 'district', 'regency'],
        'Pendidikan' => ['education', 'school_name', 'graduation_year'],
        'Keluarga' => ['father_name', 'mother_name', 'guardian_name', 'parent_job', 'guardian_phone', 'family_status', 'economic_status', 'siblings'],
        'Motivasi' => ['motivation', 'info_source', 'health_history'],
        'Berkas' => ['documents'],
        'Pernyataan' => ['agree_dorm', 'agree_pact', 'agree_privacy'],
    ];

    public function show()
    {
        return view('site.admission.form', ['site' => Setting::current(), 'batch' => Batch::acceptingApplications(), 'nextBatch' => Batch::whereIn('status', ['draft', 'open'])->where('admission_opens_at', '>', today())->orderBy('admission_opens_at')->first()]);
    }

    public function store(Request $request)
    {
        $batch = Batch::acceptingApplications();
        abort_unless($batch, 404); // AC 9.3: di luar jendela pendaftaran formulir tidak tersedia
        if (filled($request->input('website'))) { // honeypot
            return redirect()->route('site.admission');
        }

        $rules = [
            'name' => 'required|string|max:100',
            'nik' => ['required', 'digits:16', Rule::unique('applicants')->where('batch_id', $batch->id)],
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date|before:today',
            'gender' => ['required', Rule::in(array_keys(Applicant::GENDERS))],
            'phone' => 'required|string|max:30|regex:/^[0-9+\-\s]{9,}$/',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:255',
            'village' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'regency' => 'required|string|max:100',
            'education' => ['required', Rule::in(Applicant::EDUCATIONS)],
            'school_name' => 'nullable|string|max:150',
            'graduation_year' => 'nullable|integer|min:1990|max:' . (now()->year + 1),
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'parent_job' => 'nullable|string|max:100',
            'guardian_phone' => 'required|string|max:30|regex:/^[0-9+\-\s]{9,}$/',
            'family_status' => ['required', Rule::in(array_keys(Applicant::FAMILY_STATUSES))],
            'economic_status' => ['required', Rule::in(array_keys(Applicant::ECONOMIC_STATUSES))],
            'siblings' => 'nullable|integer|min:0|max:30',
            'motivation' => 'required|string|min:100|max:3000',
            'info_source' => ['required', Rule::in(Applicant::SOURCES)],
            'health_history' => 'nullable|string|max:1000',
            'agree_dorm' => 'accepted',
            'agree_pact' => 'accepted',
            'agree_privacy' => 'accepted',
        ];
        foreach (Applicant::DOCUMENTS as $key => [$label, $required]) {
            $rules["documents.{$key}"] = ($required ? 'required' : 'nullable') . '|file|mimes:jpg,jpeg,png,pdf|max:2048';
        }
        $data = $request->validate($rules, ['nik.unique' => 'NIK sudah terdaftar pada angkatan ini.', 'nik.digits' => 'NIK harus 16 digit angka.',
            'motivation.min' => 'Ceritakan motivasi Anda minimal 100 karakter.', 'accepted' => 'Pernyataan ini wajib disetujui.'], [
            'documents.photo' => 'pas foto', 'documents.id_card' => 'KTP/KK', 'documents.parent_consent' => 'surat persetujuan orang tua', 'documents.diploma' => 'ijazah', 'documents.sktm' => 'SKTM']);

        $applicant = DB::transaction(function () use ($data, $batch, $request) {
            $batch = Batch::whereKey($batch->id)->lockForUpdate()->first();
            $seq = Applicant::withTrashed()->where('batch_id', $batch->id)->count() + 1;
            $number = sprintf('PMB-%s-%03d', $batch->code, $seq);
            $documents = [];
            foreach (array_keys(Applicant::DOCUMENTS) as $key) {
                if ($file = $request->file("documents.{$key}")) {
                    $documents[$key] = $file->storeAs("applicants/{$number}", $key . '-' . Str::random(8) . '.' . $file->extension(), 'local');
                }
            }
            $fields = collect($data)->except(['documents', 'agree_dorm', 'agree_pact', 'agree_privacy'])->all();

            return Applicant::create($fields + ['batch_id' => $batch->id, 'registration_number' => $number, 'documents' => $documents,
                'is_priority' => Applicant::priorityFor($data), 'status' => 'baru', 'history' => [['status' => 'baru', 'note' => 'Formulir online', 'by' => null, 'at' => now()->toDateTimeString()]]]);
        });
        Notification::send(User::withPermission('admission-view'), new NewApplicant($applicant));

        return redirect(URL::temporarySignedRoute('site.admission.done', now()->addDays(7), $applicant->registration_number));
    }

    public function done(string $number)
    {
        $applicant = Applicant::where('registration_number', $number)->with('batch')->firstOrFail();

        return view('site.admission.done', ['applicant' => $applicant, 'site' => Setting::current(),
            'receiptUrl' => URL::temporarySignedRoute('site.admission.receipt', now()->addDays(7), $number)]);
    }

    public function receipt(string $number)
    {
        $applicant = Applicant::where('registration_number', $number)->with('batch')->firstOrFail();

        return Pdf::loadView('pdf.admission-receipt', ['applicant' => $applicant, 'site' => Setting::current()])
            ->setPaper('a4')->download("bukti-pendaftaran-{$number}.pdf");
    }

    public function status(Request $request)
    {
        $result = null;
        if ($request->isMethod('post')) {
            $data = $request->validate(['registration_number' => 'required|string|max:30', 'birth_date' => 'required|date']);
            $applicant = Applicant::where('registration_number', Str::upper(trim($data['registration_number'])))->whereDate('birth_date', $data['birth_date'])->first();
            // AC 9.3: pesan sama untuk nomor salah maupun tanggal lahir salah
            $result = $applicant ? ['name' => $applicant->name, 'number' => $applicant->registration_number, 'status' => $applicant->status, 'message' => Applicant::PUBLIC_STATUS[$applicant->status]] : false;
        }

        return view('site.admission.status', ['result' => $result, 'site' => Setting::current()]);
    }
}
