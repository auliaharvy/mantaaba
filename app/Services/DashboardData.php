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
    /** Kartu: [label, nilai, ikon, url]. */
    public static function cards(User $user): array
    {
        $open = $user->can('admission-view') ? Batch::whereIn('status', ['open', 'selection'])->latest('id')->first() : null;
        $rp = fn (int $cents) => Money::format(Money::decimal($cents));

        return array_values(array_filter([
            $user->can('member-view') ? ['Siswa aktif', Member::where('category', 'siswa')->where('status', 'aktif')->count(), 'fa-user-graduate', route('member.index')] : null,
            $user->can('member-view') ? ['Anggota aktif (semua kategori)', Member::where('status', 'aktif')->count(), 'fa-users', route('member.index')] : null,
            $open ? ["Pendaftar {$open->code} (baru)", $open->applicants()->where('status', 'baru')->count(), 'fa-user-plus', route('applicant.index', ['batch' => $open->id])] : null,
            $user->can('learning-review') ? ['Tugas belum dinilai', LessonProgress::whereNotNull('submitted_at')->whereNull('reviewed_at')->count(), 'fa-inbox', route('submission.index')] : null,
            $user->can('dorm-view') ? ['Piket terlewat hari ini', Duty::whereDate('date', today())->where('status', 'terlewat')->count(), 'fa-broom', route('duty.index')] : null,
            $user->can('journal-approve') ? ['Voucher menunggu persetujuan', Journal::where('status', 'diajukan')->count(), 'fa-check-double', route('journal.queue')] : null,
            $user->can('donation-verify') ? ['Donasi menunggu verifikasi', Donation::where('status', 'menunggu')->count(), 'fa-hand-holding-heart', route('donation.index')] : null,
            $user->can('finance-report-view') ? ['Saldo kas & bank', $rp(CoaAccount::where('is_header', false)->where(fn ($q) => $q->where('is_cash', true)->orWhere('is_bank', true))->pluck('id')
                ->sum(fn ($id) => Ledger::balanceCents($id, today()->toDateString()))), 'fa-wallet', route('ledger.cash-book')] : null,
            $user->can('kitchen-view') ? ['Biaya makan/orang hari ini', self::mealCost(), 'fa-utensils', route('kitchen.index')] : null,
            $user->can('stock-view') ? ['Barang stok menipis', Item::lowStock()->count(), 'fa-boxes', route('item.index')] : null,
            $user->can('farm-view') ? ['Catatan panen bulan ini', Harvest::where('date', '>=', today()->startOfMonth())->count(), 'fa-seedling', route('farm.index')] : null,
            $user->can('asset-view') ? ['Kendaraan jatuh tempo ≤ 30 hari', self::vehiclesDue(), 'fa-car', route('asset.index', ['kategori' => 'kendaraan'])] : null,
            $user->can('news-view') ? ['Berita terbit', Blog::published()->count(), 'fa-newspaper', route('blog.index')] : null,
            $user->can('agenda-view') ? ['Agenda mendatang', Event::public()->where('starts_at', '>=', now())->count(), 'fa-calendar-alt', route('event.index')] : null,
            $user->can('contact-message-view') ? ['Pesan belum dibaca', ContactMessage::whereNull('read_at')->count(), 'fa-envelope', route('contact-message.index')] : null,
            $user->can('user-view') ? ['Pengguna aktif', User::where('is_active', true)->count(), 'fa-user-shield', route('user.index')] : null,
        ]));
    }

    /** Grafik ApexCharts: biaya dapur 30 hari, penerimaan vs beban 12 bulan, realisasi anggaran. */
    public static function charts(User $user): array
    {
        $charts = [];
        if ($user->can('kitchen-view')) {
            $days = KitchenService::daily(today()->subDays(29)->toDateString(), today()->toDateString());
            $charts['kitchen'] = ['labels' => $days->map(fn ($d) => $d['date']->format('d/m'))->values(), 'cost' => $days->map(fn ($d) => intdiv($d['cost'], 100))->values(),
                'per_person' => $days->map(fn ($d) => $d['per_person'] !== null ? intdiv($d['per_person'], 100) : null)->values()];
        }
        if ($user->can('finance-report-view')) {
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
