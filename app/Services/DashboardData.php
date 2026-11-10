<?php

namespace App\Services;

use App\Models\AssetVehicle;
use App\Models\Batch;
use App\Models\Blog;
use App\Models\CoaAccount;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\Duty;
use App\Models\Event;
use App\Models\Harvest;
use App\Models\Item;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Member;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Isi beranda dashboard per permission (PRD 9.19). Semua kartu & grafik hanya dihitung bila pengguna berhak. */
class DashboardData
{
    /** Kartu [label, nilai, ikon, url] untuk satu modul (null = semua modul). Hanya kartu yang diizinkan yang dihitung. */
    public static function cards(User $user, ?string $module = null): array
    {
        $definitions = self::definitions($user);

        return collect($module ? [$module => $definitions[$module] ?? []] : $definitions)->flatten(1)
            ->filter(fn ($d) => $d[0])->map(fn ($d) => ($d[1])())->filter()->values()->all();
    }

    /** modul => [[boleh?, fn () => kartu]]; nilai dihitung malas agar modul lain tidak ikut di-query. */
    private static function definitions(User $user): array
    {
        $rp = fn (int $cents) => Money::format(Money::decimal($cents));
        $open = fn () => Batch::whereIn('status', ['open', 'selection'])->latest('id')->first();

        return [
            'website' => [
                [$user->can('news-view'), fn () => ['Berita terbit', Blog::published()->count(), 'fa-newspaper', route('blog.index')]],
                [$user->can('contact-message-view'), fn () => ['Pesan belum dibaca', ContactMessage::whereNull('read_at')->count(), 'fa-envelope', route('contact-message.index')]],
            ],
            'penerimaan' => [
                [$user->can('admission-view'), fn () => ($b = $open()) ? ["Pendaftar {$b->code} (baru)", $b->applicants()->where('status', 'baru')->count(), 'fa-user-plus', route('applicant.index', ['batch' => $b->id])]
                    : ['Angkatan dibuka', 'Tidak ada', 'fa-user-plus', route('batch.index')]],
                [$user->can('admission-view'), fn () => ($b = $open()) ? ["Total pendaftar {$b->code}", $b->applicants()->count(), 'fa-users', route('applicant.index', ['batch' => $b->id])] : null],
            ],
            'anggota' => [
                [$user->can('member-view'), fn () => ['Siswa aktif', Member::where('category', 'siswa')->where('status', 'aktif')->count(), 'fa-user-graduate', route('member.index')]],
                [$user->can('member-view'), fn () => ['Anggota aktif (semua kategori)', Member::where('status', 'aktif')->count(), 'fa-users', route('member.index')]],
                [$user->can('coaching-view'), fn () => ['Pelanggaran 30 hari', \App\Models\CoachingNote::where('type', 'like', 'pelanggaran%')->where('date', '>=', today()->subDays(30))->count(), 'fa-exclamation-triangle', route('coaching.index')]],
            ],
            'pembelajaran' => [
                [$user->can('learning-review'), fn () => ['Tugas belum dinilai', LessonProgress::whereNotNull('submitted_at')->whereNull('reviewed_at')->count(), 'fa-inbox', route('submission.index')]],
                [$user->can('assessment-view'), fn () => ['Sertifikat terbit ' . now()->year, \App\Models\Certificate::whereYear('issued_at', now()->year)->whereNull('revoked_at')->count(), 'fa-certificate', route('certificate.index')]],
            ],
            'asrama' => [
                [$user->can('dorm-view'), fn () => ['Piket hari ini selesai', Duty::whereDate('date', today())->whereIn('status', Duty::DONE)->count() . ' / ' . Duty::whereDate('date', today())->count(), 'fa-broom', route('duty.index')]],
                [$user->can('dorm-view'), fn () => ['Piket terlewat hari ini', Duty::whereDate('date', today())->where('status', 'terlewat')->count(), 'fa-exclamation-circle', route('duty.index')]],
                [$user->can('dorm-view'), fn () => ['Hunian kamar', Member::whereNotNull('room_id')->count() . ' / ' . \App\Models\Room::sum('capacity'), 'fa-bed', route('room.index')]],
                [$user->can('agenda-view'), fn () => ['Agenda mendatang', Event::public()->where('starts_at', '>=', now())->count(), 'fa-calendar-alt', route('event.index')]],
            ],
            'dapur' => [
                [$user->can('kitchen-view'), fn () => ['Biaya makan/orang hari ini', self::mealCost(), 'fa-utensils', route('kitchen.index')]],
                [$user->can('kitchen-view'), fn () => ['Biaya bahan bulan ini', $rp(KitchenService::daily(today()->startOfMonth()->toDateString(), today()->toDateString())->sum('cost')), 'fa-receipt', route('kitchen.report')]],
            ],
            'kebun' => [
                [$user->can('farm-view'), fn () => ['Siklus berjalan', \App\Models\FarmCycle::whereIn('status', ['berjalan', 'panen'])->count(), 'fa-seedling', route('farm.index')]],
                [$user->can('farm-view'), fn () => ['Catatan panen bulan ini', Harvest::where('date', '>=', today()->startOfMonth())->count(), 'fa-shopping-basket', route('farm.index')]],
            ],
            'stok' => [
                [$user->can('stock-view'), fn () => ['Barang stok menipis', Item::lowStock()->count(), 'fa-boxes', route('item.index')]],
                [$user->can('stock-view'), fn () => ['Pembelian menunggu persetujuan', Journal::where('source_type', 'stock_purchase')->where('status', 'diajukan')->count(), 'fa-shopping-basket', route('stock.purchases')]],
            ],
            'aset' => [
                [$user->can('asset-view'), fn () => ['Aset aktif', \App\Models\Asset::whereNull('disposed_at')->count(), 'fa-archive', route('asset.index')]],
                [$user->can('asset-view'), fn () => ['Kendaraan jatuh tempo ≤ 30 hari', self::vehiclesDue(), 'fa-car', route('asset.index', ['kategori' => 'kendaraan'])]],
                [$user->can('asset-view'), fn () => ['Kendaraan sedang dipakai', \App\Models\AssetUsage::where('status', 'dipakai')->count(), 'fa-road', route('asset.index', ['kategori' => 'kendaraan'])]],
            ],
            'keuangan' => [
                [$user->can('journal-approve'), fn () => ['Voucher menunggu persetujuan', Journal::where('status', 'diajukan')->count(), 'fa-check-double', route('journal.queue')]],
                [$user->can('donation-verify'), fn () => ['Donasi menunggu verifikasi', Donation::where('status', 'menunggu')->count(), 'fa-hand-holding-heart', route('donation.index')]],
                [$user->can('finance-report-view'), fn () => ['Saldo kas & bank', $rp(CoaAccount::where('is_header', false)->where(fn ($q) => $q->where('is_cash', true)->orWhere('is_bank', true))->pluck('id')
                    ->sum(fn ($id) => Ledger::balanceCents($id, today()->toDateString()))), 'fa-wallet', route('ledger.cash-book')]],
            ],
            'sistem' => [
                [$user->can('user-view'), fn () => ['Pengguna aktif', User::where('is_active', true)->count(), 'fa-user-shield', route('user.index')]],
            ],
        ];
    }

    /** Grafik ApexCharts: biaya dapur 30 hari, penerimaan vs beban 12 bulan, realisasi anggaran. */
    public static function charts(User $user, ?string $module = null): array
    {
        $charts = [];
        if ($user->can('kitchen-view') && in_array($module, [null, 'dapur'], true)) {
            $days = KitchenService::daily(today()->subDays(29)->toDateString(), today()->toDateString());
            $charts['kitchen'] = ['labels' => $days->map(fn ($d) => $d['date']->format('d/m'))->values(), 'cost' => $days->map(fn ($d) => intdiv($d['cost'], 100))->values(),
                'per_person' => $days->map(fn ($d) => $d['per_person'] !== null ? intdiv($d['per_person'], 100) : null)->values()];
        }
        if ($user->can('finance-report-view') && in_array($module, [null, 'keuangan'], true)) {
            $from = today()->startOfMonth()->subMonths(11);
            $rows = JournalLine::query()->join('journals', 'journals.id', '=', 'journal_lines.journal_id')->join('coa_accounts', 'coa_accounts.id', '=', 'journal_lines.coa_account_id')
                ->whereIn('journals.status', Journal::POSTED)->where('journals.date', '>=', $from->toDateString())->whereIn('coa_accounts.type', ['pendapatan', 'beban'])
                ->where('coa_accounts.code', '!=', '4-9000')
                ->where(fn ($q) => $q->whereNull('journals.source_type')->orWhere('journals.source_type', '!=', 'year_close'))
                ->groupBy('ym', 'coa_accounts.type')
                ->select(DB::raw("DATE_FORMAT(journals.date, '%Y-%m') as ym"), 'coa_accounts.type', DB::raw('SUM(journal_lines.debit) d'), DB::raw('SUM(journal_lines.credit) c'))->get();
            $months = collect(range(0, 11))->map(fn ($i) => $from->copy()->addMonths($i));
            $value = fn (string $ym, string $type) => $rows->where('ym', $ym)->where('type', $type)->sum(fn ($r) => $type === 'pendapatan' ? (float) $r->c - (float) $r->d : (float) $r->d - (float) $r->c);
            $charts['finance'] = ['labels' => $months->map(fn (Carbon $m) => $m->translatedFormat('M y')),
                'revenue' => $months->map(fn ($m) => round($value($m->format('Y-m'), 'pendapatan'))), 'expense' => $months->map(fn ($m) => round($value($m->format('Y-m'), 'beban')))];
            $budget = FinancialReports::budgetRealization(today()->year)->sortByDesc('percent')->take(8)->values();
            if ($budget->isNotEmpty()) {
                $charts['budget'] = ['labels' => $budget->map(fn ($r) => $r['budget']->account->name . ' · ' . $r['budget']->costCenter->code), 'percent' => $budget->pluck('percent')];
            }
        }

        return $charts;
    }

    /** Ringkasan anggota: progres skema & lencana, materi berikutnya, tugas mendekati tenggat, sertifikat, voucher ditolak. */
    public static function member(User $user): ?array
    {
        $member = $user->member;
        if (! $member) {
            return null;
        }
        $enrollments = $member->enrollments()->with(['skema.modules.lessons' => fn ($q) => $q->where('is_published', true), 'result'])->get()->sortBy(fn ($e) => [$e->skema->sort_order, $e->skema->id]);
        $progress = LessonProgress::where('user_id', $user->id)->get()->keyBy('lesson_id');
        $skemas = [];
        $next = null;
        $due = [];
        foreach ($enrollments as $enrollment) {
            $lessons = $enrollment->skema->modules->flatMap->lessons->values();
            $done = $lessons->filter(fn (Lesson $l) => $progress->get($l->id)?->completed_at)->count();
            $skemas[] = ['enrollment' => $enrollment, 'percent' => $lessons->count() ? (int) round($done / $lessons->count() * 100) : 0, 'passed' => (bool) $enrollment->result?->passed];
            if ($enrollment->status !== 'berjalan') {
                continue;
            }
            $next ??= $lessons->first(fn (Lesson $l) => ! $progress->get($l->id)?->completed_at);
            foreach ($lessons->where('type', 'assignment') as $lesson) {
                $date = $lesson->due_days && $enrollment->opened_at ? $enrollment->opened_at->copy()->addDays($lesson->due_days) : null;
                if ($date && ! $progress->get($lesson->id)?->submitted_at && $date->lte(today()->addDays(7))) {
                    $due[] = ['lesson' => $lesson, 'date' => $date];
                }
            }
        }

        return [
            'skemas' => $skemas, 'next' => $next, 'due' => collect($due)->sortBy('date')->values(),
            'certificates' => $member->certificates()->whereNull('revoked_at')->count(),
            'rejected' => $user->can('journal-create') ? Journal::where('created_by', $user->id)->where('status', 'ditolak')->latest('updated_at')->take(5)->get() : collect(),
        ];
    }

    private static function mealCost(): string
    {
        $day = KitchenService::daily(today()->toDateString(), today()->toDateString())->first();

        return $day['per_person'] !== null ? Money::format(Money::decimal($day['per_person'])) : '-';
    }

    private static function vehiclesDue(): int
    {
        $limit = today()->addDays(30)->toDateString();

        return AssetVehicle::whereHas('asset', fn ($q) => $q->whereNull('disposed_at'))
            ->where(fn ($q) => $q->where('tax_due_at', '<=', $limit)->orWhere('stnk_due_at', '<=', $limit)->orWhere('kir_due_at', '<=', $limit))->count();
    }
}
