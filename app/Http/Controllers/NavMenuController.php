<?php

namespace App\Http\Controllers;

use App\Models\NavMenu;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Menu Manager (PRD 9.1.2): header 2 level, footer 1 level, urutan lewat drag & drop. */
class NavMenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cms-view')->only('index');
        $this->authorizeResource(NavMenu::class, 'menu');
    }

    public function index()
    {
        $menus = NavMenu::with(['page', 'children.page'])->whereNull('parent_id')->orderBy('sort_order')->get()->groupBy('location');

        return view('dashboard.menu.index', compact('menus'));
    }

    public function create(Request $request)
    {
        $location = $request->query('location') === 'footer' ? 'footer' : 'header';

        return view('dashboard.menu.form', $this->formData(new NavMenu(['location' => $location, 'type' => 'page'])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        NavMenu::create($data + ['sort_order' => (int) NavMenu::where('location', $data['location'])->where('parent_id', $data['parent_id'])->max('sort_order') + 1]);

        return redirect()->route('menu.index')->with('success', 'Menu ditambahkan.');
    }

    public function edit(NavMenu $menu)
    {
        return view('dashboard.menu.form', $this->formData($menu));
    }

    public function update(Request $request, NavMenu $menu)
    {
        $menu->update($this->validated($request, $menu));

        return redirect()->route('menu.index')->with('success', 'Menu diubah.');
    }

    public function destroy(NavMenu $menu)
    {
        $menu->delete(); // sub-menu ikut terhapus (cascade)
        session()->flash('success', 'Menu dihapus.');

        return response()->noContent();
    }

    /** Urutan baru: daftar id sesuai urutan tampil (per tingkat; induk tidak berubah). */
    public function reorder(Request $request)
    {
        $this->authorize('update', new NavMenu());
        $ids = $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:nav_menus,id'])['order'];
        foreach (array_values($ids) as $index => $id) {
            NavMenu::whereKey($id)->update(['sort_order' => $index]);
        }
        \App\Support\SiteCache::flush(); // update massal tidak memicu event model

        return redirect()->route('menu.index')->with('success', 'Urutan menu disimpan.');
    }

    private function formData(NavMenu $menu): array
    {
        return [
            'menu' => $menu,
            'pages' => Page::orderBy('title')->pluck('title', 'id'),
            'parents' => NavMenu::where('location', 'header')->whereNull('parent_id')->whereKeyNot($menu->id)->orderBy('sort_order')->pluck('label', 'id'),
        ];
    }

    private function validated(Request $request, ?NavMenu $menu = null): array
    {
        $data = $request->validate([
            'location' => ['required', Rule::in(array_keys(NavMenu::LOCATIONS))],
            'parent_id' => ['nullable', 'integer', 'prohibited_if:location,footer',
                Rule::exists('nav_menus', 'id')->where('location', 'header')->whereNull('parent_id'), Rule::notIn([$menu?->id])],
            'label' => 'required|string|max:100',
            'type' => ['required', Rule::in(array_keys(NavMenu::TYPES))],
            'page_id' => 'required_if:type,page|nullable|exists:pages,id',
            'route_name' => ['required_if:type,route', 'nullable', Rule::in(array_keys(config('mantaaba.menu_routes')))],
            'url' => ['required_if:type,url', 'nullable', 'string', 'max:500', 'regex:#^(https?://|/)#i'],
        ], [
            'parent_id.prohibited_if' => 'Menu footer hanya satu tingkat.',
            'url.regex' => 'Awali dengan https:// atau / (alamat di situs ini).',
        ]);
        $data['parent_id'] ??= null;
        if ($data['parent_id'] && $menu?->children()->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'Menu yang punya sub-menu tidak bisa dijadikan sub-menu (maksimal 2 tingkat).']);
        }
        // simpan hanya target yang sesuai jenis
        foreach (['page' => 'page_id', 'route' => 'route_name', 'url' => 'url'] as $type => $field) {
            if ($data['type'] !== $type) {
                $data[$field] = null;
            }
        }

        return $data + ['open_new_tab' => $request->boolean('open_new_tab'), 'is_active' => $request->boolean('is_active')];
    }
}
