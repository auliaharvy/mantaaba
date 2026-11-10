<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Skema;
use App\Services\SkemaEnroller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Angkatan (PRD 9.2). */
class BatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:batch-view')->only(['index', 'datatable']);
        $this->authorizeResource(Batch::class, 'batch');
    }

    public function index()
    {
        return view('dashboard.batch.index');
    }

    public function datatable()
    {
        return DataTables::eloquent(Batch::query()->withCount(['applicants', 'applicants as accepted_count' => fn ($q) => $q->where('status', 'diterima')]))
            ->editColumn('status', fn ($b) => Batch::STATUSES[$b->status])
            ->addColumn('window', fn ($b) => collect([$b->admission_opens_at?->translatedFormat('d M Y'), $b->admission_closes_at?->translatedFormat('d M Y')])->filter()->implode(' – ') ?: '-')
            ->addColumn('accepted', fn ($b) => "{$b->accepted_count} / {$b->quota}")
            ->addColumn('action', fn ($b) => view('components.dashboard.row-actions', [
                'show' => route('batch.show', $b), 'edit' => route('batch.edit', $b),
                'delete' => $b->applicants_count === 0 ? route('batch.destroy', $b) : null, 'confirm' => null,
            ])->render())
            ->rawColumns(['action'])->addIndexColumn()->make(true);
    }

    public function create()
    {
        $year = now()->year;

        return view('dashboard.batch.form', ['batch' => new Batch(['quota' => 6, 'status' => 'draft', 'code' => $year . '-' . (Batch::where('code', 'like', "{$year}-%")->count() + 1)])]);
    }

    public function store(Request $request)
    {
        $batch = Batch::create($this->validated($request));

        return redirect()->route('batch.show', $batch)->with('success', 'Angkatan dibuat.');
    }

    public function show(Request $request, Batch $batch)
    {
        $counts = $batch->applicants()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $students = $batch->members()->with('enrollments.result')->orderBy('name')->get();
        $skemas = Skema::ordered()->get();
        $from = today()->startOfMonth()->toDateString();
        $dutyRates = $students->mapWithKeys(fn ($m) => [$m->id => \App\Models\Duty::completionRate($m->id, $from, today()->toDateString())]);
        $events = \App\Models\Event::where(fn ($q) => $q->where('batch_id', $batch->id)->orWhere('audience', 'anggota'))->latest('starts_at')->take(20)
            ->withCount(['participants as present_count' => fn ($q) => $q->where('status', 'hadir')->whereIn('user_id', $batch->members()->whereNotNull('user_id')->select('user_id'))])->get();

        return view('dashboard.batch.show', compact('batch', 'counts', 'students', 'skemas', 'dutyRates', 'events') + ['tab' => $request->query('tab', 'pendaftar')]);
    }

    public function edit(Batch $batch)
    {
        return view('dashboard.batch.form', compact('batch'));
    }

    public function update(Request $request, Batch $batch)
    {
        $batch->update($this->validated($request, $batch));
        $message = 'Angkatan diubah.';
        if ($batch->wasChanged('status') && $batch->status === 'running') {
            $count = app(SkemaEnroller::class)->enrollBatch($batch); // PRD 9.2: skema pertama dibuka, lainnya terkunci
            $message .= " {$count} pendaftaran skema dibuat untuk siswa angkatan ini.";
        }

        return redirect()->route('batch.show', $batch)->with('success', $message);
    }

    /** Buka satu skema untuk seluruh siswa angkatan (PRD 9.6). */
    public function openSkema(Batch $batch, Skema $skema, SkemaEnroller $enroller)
    {
        abort_unless(auth()->user()->can('learning-update'), 403);
        $enroller->enrollBatch($batch);
        $count = $enroller->openSkema($batch, $skema);

        return redirect()->route('batch.show', [$batch, 'tab' => 'progres'])->with('success', "Skema {$skema->name} dibuka untuk {$count} siswa.");
    }

    public function destroy(Batch $batch)
    {
        abort_if($batch->applicants()->withTrashed()->exists() || $batch->members()->exists(), 422, 'Angkatan sudah memiliki pendaftar/siswa.');
        $batch->delete();
        session()->flash('success', 'Angkatan dihapus.');

        return response()->noContent();
    }

    private function validated(Request $request, ?Batch $batch = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('batches')->ignore($batch)],
            'name' => 'required|string|max:255',
            'admission_opens_at' => 'nullable|date',
            'admission_closes_at' => 'nullable|date|after_or_equal:admission_opens_at',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'quota' => 'required|integer|min:1|max:100',
            // PRD 9.2 hard-stop: hanya satu angkatan berstatus open
            'status' => ['required', Rule::in(array_keys(Batch::STATUSES)), function ($attr, $value, $fail) use ($batch) {
                if ($value === 'open' && Batch::where('status', 'open')->whereKeyNot($batch?->id)->exists()) {
                    $fail('Masih ada angkatan lain yang berstatus pendaftaran dibuka. Tutup dulu angkatan tersebut.');
                }
            }],
            'description' => 'nullable|string|max:2000',
            'requirements_note' => 'nullable|string|max:1000',
        ], ['code.regex' => 'Kode hanya huruf, angka, dan tanda minus (mis. 2027-1).']);
    }
}
