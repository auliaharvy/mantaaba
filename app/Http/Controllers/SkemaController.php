<?php

namespace App\Http\Controllers;

use App\Models\Skema;
use App\Support\Html;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Skema & kurikulum (PRD 9.6): CRUD skema, susunan modul & materi. */
class SkemaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Skema::class, 'skema');
    }

    public function index()
    {
        return view('dashboard.skema.index', ['skemas' => Skema::ordered()->withCount(['modules', 'lessons', 'enrollments'])->get()]);
    }

    public function create()
    {
        return view('dashboard.skema.form', ['skema' => new Skema(['is_public' => true, 'sort_order' => Skema::max('sort_order') + 1])]);
    }

    public function store(Request $request)
    {
        $skema = Skema::create($this->validated($request));

        return redirect()->route('skema.show', $skema)->with('success', 'Skema dibuat. Tambahkan modul & materi.');
    }

    public function show(Skema $skema)
    {
        return view('dashboard.skema.show', ['skema' => $skema->load('modules.lessons')]);
    }

    public function edit(Skema $skema)
    {
        return view('dashboard.skema.form', compact('skema'));
    }

    public function update(Request $request, Skema $skema)
    {
        $skema->update($this->validated($request, $skema));

        return redirect()->route('skema.show', $skema)->with('success', 'Skema diubah.');
    }

    public function destroy(Skema $skema)
    {
        abort_if($skema->enrollments()->exists(), 422, 'Skema sudah diikuti siswa; tidak bisa dihapus.');
        $skema->delete();
        session()->flash('success', 'Skema dihapus.');

        return response()->noContent();
    }

    private function validated(Request $request, ?Skema $skema = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', Rule::notIn(['INK']), Rule::unique('skemas')->ignore($skema)],
            'name' => 'required|string|max:255',
            'month_label' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:2000',
            'objectives' => 'nullable|string',
            'pass_score' => 'nullable|integer|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'matrix' => 'nullable|array|max:10',
            'matrix.*.competency' => 'nullable|string|max:100',
            'matrix.*.technical' => 'nullable|string|max:500',
            'matrix.*.asabiyah' => 'nullable|string|max:500',
            'matrix.*.fastabiqul' => 'nullable|string|max:500',
            'certificate_template' => 'nullable|' . Upload::IMAGE_RULE,
            'signatories' => 'nullable|array|max:2',
            'signatories.*.name' => 'nullable|string|max:100',
            'signatories.*.title' => 'nullable|string|max:100',
            'signatures.*' => 'nullable|image|mimes:png,webp|max:1024',
        ], ['code.regex' => 'Kode huruf kapital/angka tanpa spasi (mis. SIA).', 'code.not_in' => 'INK dipakai untuk sertifikat kelulusan inkubasi.']);

        $data['objectives'] = Html::clean($data['objectives'] ?? null);
        $data['character_matrix'] = array_values(array_filter($data['matrix'] ?? [], fn ($row) => filled($row['competency'] ?? null)));
        $data['is_public'] = $request->boolean('is_public');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        unset($data['matrix'], $data['certificate_template'], $data['signatories'], $data['signatures']);
        if ($request->hasFile('certificate_template')) {
            $data['certificate_template'] = Upload::store($request->file('certificate_template'), 'skemas/certificates', $skema?->certificate_template, 3508);
        }
        // penandatangan maks. 2: nama, jabatan, tanda tangan PNG transparan
        $old = $skema?->certificate_signatories ?? [];
        $data['certificate_signatories'] = collect([0, 1])->map(function ($i) use ($request, $old) {
            $signature = $old[$i]['signature'] ?? null;
            if ($request->hasFile("signatures.{$i}")) {
                $signature = Upload::store($request->file("signatures.{$i}"), 'skemas/signatures', $signature, 800);
            }

            return ['name' => $request->input("signatories.{$i}.name"), 'title' => $request->input("signatories.{$i}.title"), 'signature' => $signature];
        })->filter(fn ($s) => filled($s['name']))->values()->all();

        return $data;
    }
}
