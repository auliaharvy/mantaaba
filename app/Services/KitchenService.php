<?php

namespace App\Services;

use App\Models\Harvest;
use App\Models\Item;
use App\Models\KitchenLog;
use App\Models\KitchenMenu;
use App\Models\MealPlan;
use App\Models\Member;
use App\Models\Setting;
use App\Models\StockMove;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Dapur (PRD 9.12): kebutuhan bahan & daftar belanja, masak dari stok Gudang Dapur, biaya per orang per hari. */
class KitchenService
{
    public function __construct(private StockLedger $ledger)
    {
    }

    public static function warehouse(): Warehouse
    {
        return Warehouse::byCode('DPR') ?? Warehouse::firstOrFail();
    }

    /** Penghuni aktif (untuk porsi default & biaya per orang). shortcut: jumlah saat ini, bukan histori per tanggal. */
    public static function residents(): int
    {
        $count = Member::where('status', 'aktif')->whereNotNull('room_id')->count();

        return $count ?: (int) (Setting::current()->default_residents ?? 0);
    }

    /** Kebutuhan bahan rencana dalam rentang tanggal dikurangi stok Gudang Dapur. */
    public static function needs(string $from, string $to): Collection
    {
        $need = [];
        foreach (MealPlan::whereBetween('date', [$from, $to])->where('status', 'rencana')->get() as $plan) {
            foreach ($plan->requirements() as $itemId => $qty) {
                $need[$itemId] = ($need[$itemId] ?? 0) + $qty;
            }
        }
        $stock = Item::balances(self::warehouse()->id);

        return Item::whereIn('id', array_keys($need))->orderBy('name')->get()->map(fn (Item $item) => [
            'item' => $item, 'need' => round($need[$item->id], 3), 'stock' => $stock[$item->id] ?? 0,
            'buy' => max(0, round($need[$item->id] - ($stock[$item->id] ?? 0), 3)),
        ]);
    }

    /**
     * Masak: semua bahan dicek dulu (pesan menyebut setiap bahan yang kurang), lalu keluar_pakai per bahan.
     * $ingredients: [item_id => qty].
     */
    public function cook(MealPlan $plan, array $ingredients, array $data, User $user): KitchenLog
    {
        abort_if($plan->status === 'dimasak', 422, 'Waktu makan ini sudah dicatat dimasak.');
        $ingredients = array_filter(array_map('floatval', $ingredients), fn ($q) => $q > 0);
        if (! $ingredients) {
            throw ValidationException::withMessages(['ingredients' => 'Isi minimal satu bahan.']);
        }
        $warehouse = self::warehouse();
        $items = Item::whereIn('id', array_keys($ingredients))->get()->keyBy('id');
        $stock = Item::balances($warehouse->id);
        $short = [];
        foreach ($ingredients as $itemId => $qty) {
            $available = $stock[$itemId] ?? 0;
            if ($available + 0.0005 < $qty) {
                $short[] = "{$items[$itemId]->name}: kurang " . StockLedger::qty($qty - $available) . " {$items[$itemId]->unit} (stok " . StockLedger::qty($available) . ')';
            }
        }
        if ($short) {
            throw ValidationException::withMessages(['ingredients' => 'Bahan tidak cukup — ' . implode('; ', $short) . '.']);
        }

        return DB::transaction(function () use ($plan, $ingredients, $data, $user, $warehouse, $items) {
            $log = KitchenLog::create($data + ['meal_plan_id' => $plan->id, 'cooked_at' => $data['cooked_at'] ?? now(), 'created_by' => $user->id]);
            $cost = 0;
            foreach ($ingredients as $itemId => $qty) {
                $move = $this->ledger->record('keluar_pakai', $plan->date->toDateString(), $items[$itemId], $warehouse, $qty, $user, $log,
                    ['note' => MealPlan::MEALS[$plan->meal] . ' ' . $plan->date->format('d/m')]);
                $cost -= Money::cents($move->total_cost);
            }
            $log->update(['cost_total' => Money::decimal($cost)]);
            $plan->update(['status' => 'dimasak']);

            return $log;
        });
    }

    /** Angka dapur per hari dalam rentang (sen). */
    public static function daily(string $from, string $to): Collection
    {
        $logs = KitchenLog::with('plan')->whereHas('plan', fn ($q) => $q->whereBetween('date', [$from, $to]))->get()->groupBy(fn ($l) => $l->plan->date->toDateString());
        $garden = Harvest::where('destination', 'dapur')->whereBetween('date', [$from, $to])->get()->groupBy(fn ($h) => $h->date->toDateString());
        $residents = self::residents();
        $budget = Money::cents(Setting::current()->meal_budget_per_person_day ?? 0);
        $days = collect();
        for ($d = Carbon::parse($from); $d->lte(Carbon::parse($to)); $d->addDay()) {
            $key = $d->toDateString();
            $cost = ($logs[$key] ?? collect())->sum(fn ($l) => Money::cents($l->cost_total));
            $days[$key] = [
                'date' => $d->copy(), 'cost' => $cost, 'residents' => $residents,
                'per_person' => $residents ? intdiv($cost, $residents) : null, 'budget' => $budget ?: null,
                'portions' => ($logs[$key] ?? collect())->sum('actual_portions'), 'leftover' => ($logs[$key] ?? collect())->sum('leftover_portions'),
                'garden' => ($garden[$key] ?? collect())->sum(fn (Harvest $h) => (int) round((float) $h->qty * Money::cents($h->market_price))),
            ];
        }

        return $days;
    }

    /** Laporan bulanan: tren harian + menu terbanyak + bahan termahal. */
    public static function monthly(int $year, int $month): array
    {
        $from = Carbon::create($year, $month)->startOfMonth();
        $to = $from->copy()->endOfMonth()->min(today()->max($from));
        $days = self::daily($from->toDateString(), $to->toDateString());
        $cooked = $days->filter(fn ($d) => $d['cost'] > 0);
        $plans = MealPlan::whereBetween('date', [$from->toDateString(), $to->toDateString()])->where('status', 'dimasak')->get();
        $menuCounts = $plans->flatMap(fn ($p) => $p->menu_ids ?? [])->countBy()->sortDesc()->take(5);
        $menus = KitchenMenu::whereIn('id', $menuCounts->keys())->get()->keyBy('id');
        $ingredients = StockMove::with('item')->where('source_type', (new KitchenLog)->getMorphClass())->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get()
            ->groupBy('item_id')->map(fn ($g) => ['item' => $g->first()->item, 'qty' => -(float) $g->sum('qty'), 'cost' => -$g->sum(fn ($m) => Money::cents($m->total_cost))])
            ->sortByDesc('cost')->take(5)->values();
        $portions = $days->sum('portions');

        return [
            'days' => $days, 'from' => $from, 'to' => $to,
            'cost' => $days->sum('cost'), 'garden' => $days->sum('garden'),
            'avg_per_person' => $cooked->isNotEmpty() && $cooked->first()['residents'] ? intdiv($cooked->sum('cost'), $cooked->count() * $cooked->first()['residents']) : null,
            'leftover_percent' => $portions ? round($days->sum('leftover') / $portions * 100, 1) : null,
            'top_menus' => $menuCounts->map(fn ($n, $id) => ['menu' => $menus[$id] ?? null, 'count' => $n])->filter(fn ($r) => $r['menu'])->values(),
            'top_ingredients' => $ingredients,
        ];
    }
}
