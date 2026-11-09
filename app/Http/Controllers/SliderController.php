<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class SliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:slider-view')->only(['index', 'datatable']);
        $this->authorizeResource(Slider::class, 'slider');
    }

    public function index()
    {
        return view('dashboard.slider.index');
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(Slider::query())
            ->addColumn('preview', fn ($s) => '<img src="' . e(Storage::disk('public')->url($s->image)) . '" alt="" class="h-50px rounded">')
            ->editColumn('is_active', fn ($s) => $s->is_active ? 'Aktif' : 'Nonaktif')
            ->addColumn('action', fn ($s) => view('components.dashboard.row-actions', [
                'show' => null, 'edit' => route('slider.edit', $s), 'delete' => route('slider.destroy', $s), 'confirm' => null,
            ])->render())
            ->rawColumns(['preview', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.slider.form', ['slider' => new Slider(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        Slider::create($this->validated($request));

        return redirect()->route('slider.index')->with('success', 'Slider ditambahkan.');
    }

    public function edit(Slider $slider)
    {
        return view('dashboard.slider.form', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $slider->update($this->validated($request, $slider));

        return redirect()->route('slider.index')->with('success', 'Slider diubah.');
    }

    public function destroy(Slider $slider)
    {
        Upload::delete($slider->image);
        $slider->delete();
        session()->flash('success', 'Slider dihapus.');

        return response()->noContent();
    }

    private function validated(Request $request, ?Slider $slider = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => ($slider ? 'nullable|' : 'required|') . Upload::IMAGE_RULE,
            'button_text' => 'nullable|string|max:50',
            'button_url' => 'nullable|url|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = Upload::store($request->file('image'), 'sliders', $slider?->image);
        }

        return $data + ['is_active' => $request->boolean('is_active'), 'sort_order' => (int) $request->input('sort_order')];
    }
}
