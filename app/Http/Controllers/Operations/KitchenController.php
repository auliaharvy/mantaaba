<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Duty;
use App\Models\Item;
use App\Models\KitchenMenu;
use App\Models\MealPlan;
use App\Services\KitchenService;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Dapur (PRD 9.12): dashboard harian, resep, rencana mingguan, daftar belanja, masak, laporan bulanan. */
class KitchenController extends Controller
{
    public function __construct(private KitchenService $kitchen)
    {
        $this->middleware('permission:kitchen-view')->only(['index', 'menus', 'plans', 'needs', 'report']);
        $this->middleware('permission:kitchen-create')->only(['createMenu', 'storeMenu']);
        $this->middleware('permission:kitchen-update')->only(['editMenu', 'updateMenu']);
        $this->middleware('permission:kitchen-log')->only(['savePlans', 'cookForm', 'cook']);
    }

    public function index(Request $request)
    {
        $date = Carbon::parse($request->input('tanggal', today()->toDateString()));
        $plans = MealPlan::with('log')->whereDate('date', $date)->get()->keyBy('meal');
        $menus = KitchenMenu::whereIn('id', $plans->flatMap(fn ($p) => $p->menu_ids ?? []))->get()->keyBy('id');
        $day = KitchenService::daily($date->toDateString(), $date->toDateString())->first();
        $duties = Duty::with('member:id,name')->whereDate('date', $date)->whereHas('area', fn ($q) => $q->where('name', 'like', '%Dapur%'))->get();
        $warehouse = KitchenService::warehouse();
        $balances = Item::balances($warehouse->id);
        $low = Item::where('is_active', true)->where('min_stock', '>', 0)->whereIn('category', ['bahan_pangan', 'hasil_panen'])->orderBy('name')->get()
            ->filter(fn ($i) => ($balances[$i->id] ?? 0) < (float) $i->min_stock)->each(fn ($i) => $i->setAttribute('balance', $balances[$i->id] ?? 0));

        return view('dashboard.kitchen.index', compact('date', 'plans', 'menus', 'day', 'duties', 'low'));
    }

    public function menus()
    {
        return view('dashboard.kitchen.menus', ['menus' => KitchenMenu::orderBy('category')->orderBy('name')->get(), 'items' => Item::pluck('name', 'id')]);
    }

    public function createMenu()
    {
        return view('dashboard.kitchen.menu-form', ['menu' => new KitchenMenu(['category' => 'lauk', 'is_active' => true]), 'items' => $this->ingredientItems()]);
    }

    public function storeMenu(Request $request)
    {
        KitchenMenu::create($this->menuData($request));

        return redirect()->route('kitchen.menus')->with('success', 'Menu ditambahkan.');
    }

    public function editMenu(KitchenMenu $menu)
    {
        return view('dashboard.kitchen.menu-form', ['menu' => $menu, 'items' => $this->ingredientItems()]);
    }

    public function updateMenu(Request $request, KitchenMenu $menu)
    {
        $menu->update($this->menuData($request));

        return redirect()->route('kitchen.menus')->with('success', 'Menu diubah.');
    }

    /** Rencana mingguan: grid 7 hari × waktu makan. */
    public function plans(Request $request)
    {
        $start = Carbon::parse($request->input('minggu', today()->toDateString()))->startOfWeek();
        $end = $start->copy()->addDays(6);
        $plans = MealPlan::whereBetween('date', [$start->toDateString(), $end->toDateString()])->get()->keyBy(fn ($p) => $p->date->toDateString() . '|' . $p->meal);

        return view('dashboard.kitchen.plans', ['start' => $start, 'plans' => $plans, 'menus' => KitchenMenu::where('is_active', true)->orderBy('category')->orderBy('name')->get(),
            'residents' => KitchenService::residents()]);
    }

    public function savePlans(Request $request)
    {
        $data = $request->validate(['plans' => 'array', 'plans.*.*.menu_ids' => 'nullable|array', 'plans.*.*.menu_ids.*' => 'exists:kitchen_menus,id',
            'plans.*.*.portions' => 'nullable|integer|min:1|max:1000']);
        foreach ($data['plans'] ?? [] as $date => $meals) {
            abort_unless(strtotime($date), 422);
            foreach ($meals as $meal => $row) {
                abort_unless(isset(MealPlan::MEALS[$meal]), 422);
                $plan = MealPlan::where('date', $date)->where('meal', $meal)->first();
                if ($plan?->status === 'dimasak') {
                    continue; // sudah dimasak: tidak diubah
                }
                if (empty($row['menu_ids'])) {
                    $plan?->delete();
                    continue;
                }
                MealPlan::updateOrCreate(['date' => $date, 'meal' => $meal], ['menu_ids' => array_map('intval', $row['menu_ids']), 'planned_portions' => $row['portions'] ?: KitchenService::residents() ?: 1]);
            }
        }

        return back()->with('success', 'Rencana menu disimpan.');
    }

    /** Hitung kebutuhan → daftar belanja (kebutuhan − stok Gudang Dapur). */
    public function needs(Request $request)
    {
        $start = Carbon::parse($request->input('minggu', today()->toDateString()))->startOfWeek();
        $rows = KitchenService::needs($start->toDateString(), $start->copy()->addDays(6)->toDateString());

        return view('dashboard.kitchen.needs', ['start' => $start, 'rows' => $rows, 'warehouse' => KitchenService::warehouse()]);
    }

    public function cookForm(MealPlan $plan)
    {
        abort_if($plan->status === 'dimasak', 422, 'Waktu makan ini sudah dimasak.');
        $portions = old('actual_portions', $plan->planned_portions);

        return view('dashboard.kitchen.cook', ['plan' => $plan, 'menus' => $plan->menus(), 'needs' => $plan->requirements(),
            'items' => Item::where('is_active', true)->whereIn('category', ['bahan_pangan', 'hasil_panen', 'habis_pakai'])->orderBy('name')->get(),
            'stock' => Item::balances(KitchenService::warehouse()->id), 'portions' => $portions]);
    }

    public function cook(Request $request, MealPlan $plan)
    {
        $data = $request->validate([
            'ingredients' => 'required|array', 'ingredients.*' => 'nullable|numeric|min:0',
            'extra_item_id' => 'nullable|exists:items,id', 'extra_qty' => 'nullable|required_with:extra_item_id|numeric|gt:0',
            'actual_portions' => 'required|integer|min:1|max:1000', 'leftover_portions' => 'nullable|integer|min:0|lte:actual_portions',
            'note' => 'nullable|string|max:500', 'photo' => 'nullable|' . Upload::IMAGE_RULE,
        ]);
        $ingredients = $data['ingredients'];
        if (! empty($data['extra_item_id'])) {
            $ingredients[$data['extra_item_id']] = (float) ($ingredients[$data['extra_item_id']] ?? 0) + (float) $data['extra_qty'];
        }
        $this->kitchen->cook($plan, $ingredients, [
            'actual_portions' => $data['actual_portions'], 'leftover_portions' => $data['leftover_portions'] ?? 0, 'note' => $data['note'] ?? null,
            'photo' => $request->hasFile('photo') ? Upload::store($request->file('photo'), 'kitchen', null, 1280) : null,
        ], $request->user());

        return redirect()->route('kitchen.index', ['tanggal' => $plan->date->toDateString()])->with('success', 'Masakan dicatat; bahan keluar dari Gudang Dapur.');
    }

    public function report(Request $request)
    {
        $month = Carbon::parse(($request->input('bulan') ?: today()->format('Y-m')) . '-01');

        return view('dashboard.kitchen.report', ['month' => $month, 'r' => KitchenService::monthly($month->year, $month->month)]);
    }

    private function menuData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'category' => ['required', Rule::in(array_keys(KitchenMenu::CATEGORIES))],
            'ingredients' => 'nullable|array', 'ingredients.*.item_id' => 'nullable|exists:items,id', 'ingredients.*.qty' => 'nullable|required_with:ingredients.*.item_id|numeric|gt:0',
            'notes' => 'nullable|string|max:500', 'is_active' => 'boolean',
        ], ['ingredients.*.qty.required_with' => 'Isi jumlah per porsi untuk setiap bahan.']);
        $data['ingredients'] = collect($data['ingredients'] ?? [])->filter(fn ($r) => ! empty($r['item_id']))
            ->map(fn ($r) => ['item_id' => (int) $r['item_id'], 'qty' => (float) $r['qty']])->unique('item_id')->values()->all();
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function ingredientItems()
    {
        return Item::where('is_active', true)->whereIn('category', ['bahan_pangan', 'hasil_panen', 'habis_pakai'])->orderBy('name')->get();
    }
}
