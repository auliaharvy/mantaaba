<?php

namespace App\Services;

use App\Models\CoaAccount;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\User;
use App\Notifications\VoucherDecided;
use App\Notifications\VoucherSubmitted;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Siklus voucher (PRD 9.13.2): simpan draf → ajukan → posting/tolak → balik.
 * Semua aturan hard-stop dicek di sini, bukan di controller, agar berlaku juga untuk jurnal otomatis.
 */
class JournalPoster
{
    /** Simpan/ubah draf beserta baris. $lines: [[coa_account_id, debit, credit, cost_center_id, party_id, memo], ...] */
    public function saveDraft(array $header, array $lines, User $user, ?Journal $journal = null): Journal
    {
        if ($journal && ! $journal->isEditable()) {
            abort(403, 'Voucher yang sudah diajukan/terposting tidak bisa diubah. Koreksi lewat Balik.');
        }
        if ($header['voucher_type'] === 'AJV' && ! $user->can('journal-approve') && ! ($header['is_system'] ?? false)) {
            throw ValidationException::withMessages(['voucher_type' => 'AJV hanya untuk penyetuju jurnal atau sistem.']);
        }

        return DB::transaction(function () use ($header, $lines, $user, $journal) {
            $journal ??= new Journal(['created_by' => $user->id]);
            $journal->fill($header + ['fiscal_period_id' => FiscalPeriod::forDate($header['date'])->id]);
            if ($journal->status === 'ditolak') {
                $journal->status = 'draft';
            }
            $journal->save();
            $journal->lines()->delete();
            foreach ($lines as $line) {
                $journal->lines()->create([
                    'coa_account_id' => $line['coa_account_id'], 'memo' => $line['memo'] ?? null,
                    'debit' => Money::decimal(Money::cents($line['debit'] ?? 0)), 'credit' => Money::decimal(Money::cents($line['credit'] ?? 0)),
                    'cost_center_id' => $line['cost_center_id'] ?? $header['cost_center_id'] ?? null,
                    'party_id' => $line['party_id'] ?? null,
                    'farm_cycle_id' => $line['farm_cycle_id'] ?? null, 'asset_id' => $line['asset_id'] ?? null,
                ]);
            }

            return $journal->load('lines.account');
        });
    }

    public function submit(Journal $journal, User $user): Journal
    {
        abort_unless($journal->isEditable(), 422, 'Voucher ini sudah diajukan atau diposting.');
        $journal->load('lines.account', 'period');
        $this->validate($journal);
        $this->ensurePeriodOpen($journal);
        if (! $journal->is_system && empty($journal->attachments)) {
            throw ValidationException::withMessages(['attachments' => 'Lampirkan minimal satu dokumen pendukung (nota, kuitansi, faktur).']);
        }
        $journal->forceFill(['status' => 'diajukan', 'submitted_by' => $user->id, 'submitted_at' => now(), 'reject_reason' => null])->save();
        $approvers = User::withPermission('journal-approve')->reject(fn ($u) => in_array($u->id, [$user->id, $journal->created_by], true));
        Notification::send($approvers, new VoucherSubmitted($journal));

        return $journal;
    }

    public function post(Journal $journal, User $approver): Journal
    {
        abort_unless($journal->status === 'diajukan', 422, 'Hanya voucher berstatus diajukan yang bisa diposting.');
        $selfPosted = $journal->is_system && in_array($journal->source_type, Journal::SELF_POSTED_SOURCES, true);
        if ($selfPosted) {
            abort_unless($approver->can('period-close'), 403, 'Saldo awal & penutupan tahun diposting oleh pemegang hak tutup periode.');
        } elseif (in_array($approver->id, array_filter([$journal->created_by, $journal->submitted_by]), true)) {
            // aturan 6 — berlaku juga untuk Super Admin (tidak lewat Gate)
            abort(403, 'Pembuat/pengaju dan penyetuju voucher harus orang yang berbeda.');
        }

        DB::transaction(function () use ($journal, $approver) {
            $journal = Journal::whereKey($journal->id)->lockForUpdate()->with('lines.account', 'period')->first();
            $this->validate($journal);
            $this->ensurePeriodOpen($journal);
            $this->ensureCashNotNegative($journal);
            $journal->forceFill(['number' => $this->nextNumber($journal), 'status' => 'terposting', 'approved_by' => $approver->id, 'posted_at' => now()])->save();
        });
        $this->notifyDecision($journal->refresh(), 'posted');

        return $journal;
    }

    public function reject(Journal $journal, User $approver, string $reason): Journal
    {
        abort_unless($journal->status === 'diajukan', 422, 'Hanya voucher berstatus diajukan yang bisa ditolak.');
        $journal->forceFill(['status' => 'ditolak', 'reject_reason' => $reason, 'approved_by' => $approver->id])->save();
        $this->notifyDecision($journal, 'rejected');

        return $journal;
    }

    /** Aturan 10: koreksi voucher terposting = voucher pembalik (debit/kredit ditukar) yang langsung terposting. */
    public function reverse(Journal $journal, User $user, ?string $date = null): Journal
    {
        abort_unless($journal->status === 'terposting', 422, 'Hanya voucher terposting yang bisa dibalik.');

        return DB::transaction(function () use ($journal, $user, $date) {
            $journal = Journal::whereKey($journal->id)->lockForUpdate()->with('lines')->first();
            $date ??= today()->toDateString();
            $reversal = new Journal([
                'voucher_type' => $journal->voucher_type, 'date' => $date, 'fiscal_period_id' => FiscalPeriod::forDate($date)->id,
                'description' => "Pembalik {$journal->number}: {$journal->description}", 'party_id' => $journal->party_id, 'cost_center_id' => $journal->cost_center_id,
                'reference_no' => $journal->number, 'is_system' => true, 'reversal_of_id' => $journal->id, 'created_by' => $user->id,
            ]);
            $reversal->save();
            foreach ($journal->lines as $line) {
                $reversal->lines()->create(['debit' => $line->credit, 'credit' => $line->debit] + $line->only(['coa_account_id', 'cost_center_id', 'party_id', 'farm_cycle_id', 'asset_id', 'memo']));
            }
            $reversal->load('lines.account', 'period');
            $this->validate($reversal, checkType: false);
            $this->ensurePeriodOpen($reversal);
            $this->ensureCashNotNegative($reversal);
            $reversal->forceFill(['number' => $this->nextNumber($reversal), 'status' => 'terposting', 'submitted_by' => $user->id, 'submitted_at' => now(),
                'approved_by' => $user->id, 'posted_at' => now()])->save();
            $journal->forceFill(['status' => 'dibalik'])->save();

            return $reversal;
        });
    }

    /** Jurnal otomatis/sistem langsung berstatus diajukan (PRD 9.16); $sourceType = morph class atau penanda (mis. opening_balance). */
    public function createSystem(string $type, string $date, string $description, array $lines, User $user, ?string $sourceType = null, ?int $sourceId = null, array $extra = []): Journal
    {
        $journal = $this->saveDraft(['voucher_type' => $type, 'date' => $date, 'description' => $description, 'is_system' => true,
            'source_type' => $sourceType, 'source_id' => $sourceId] + $extra, $lines, $user);

        return $this->submit($journal, $user);
    }

    /** Aturan 1–3, 8, 9 + aturan per tipe voucher. */
    public function validate(Journal $journal, bool $checkType = true): void
    {
        $lines = $journal->lines->loadMissing('account');
        $errors = [];
        if ($lines->count() < 2) {
            $errors[] = 'Voucher minimal 2 baris.';
        }
        foreach ($lines->values() as $i => $line) {
            $n = $i + 1;
            $debit = Money::cents($line->debit);
            $credit = Money::cents($line->credit);
            if (($debit > 0) === ($credit > 0) || $debit < 0 || $credit < 0) {
                $errors[] = "Baris {$n}: isi debit ATAU kredit dengan nilai lebih dari 0.";
            }
            if (! $line->account->isPostable()) {
                $errors[] = "Baris {$n}: akun {$line->account->label()} adalah akun header/nonaktif.";
            }
            if (($line->account->is_receivable || $line->account->is_payable) && ! $line->party_id) {
                $errors[] = "Baris {$n}: akun piutang/utang wajib diisi pihak.";
            }
            if ($line->account->needsCostCenter() && ! $line->cost_center_id) {
                $errors[] = "Baris {$n}: akun pendapatan/beban wajib diisi pusat biaya.";
            }
        }
        $difference = $lines->sum(fn ($l) => Money::cents($l->debit)) - $lines->sum(fn ($l) => Money::cents($l->credit));
        if ($difference !== 0) {
            $errors[] = 'Total debit dan kredit tidak seimbang (selisih ' . Money::format(Money::decimal(abs($difference))) . ').';
        }
        // saldo awal (MJV) memuat saldo kas/bank: satu-satunya pengecualian aturan tipe (PRD 9.13.3)
        $checkType = $checkType && ! ($journal->is_system && $journal->source_type === 'opening_balance');
        if ($checkType && ($typeError = $this->typeRule($journal->voucher_type, $lines))) {
            $errors[] = $typeError;
        }
        if ($errors) {
            throw ValidationException::withMessages(['lines' => $errors]);
        }
    }

    /** Aturan 8 tipe voucher (Whitepaper 2.2.1). */
    private function typeRule(string $type, Collection $lines): ?string
    {
        $has = fn (callable $where) => $lines->contains(fn (JournalLine $l) => $where($l, $l->account));
        $cash = $has(fn ($l, $a) => $a->is_cash);
        $bank = $has(fn ($l, $a) => $a->is_bank);

        return match ($type) {
            'BPV' => ! $has(fn ($l, $a) => $a->is_bank && Money::cents($l->credit) > 0) ? 'BPV wajib memiliki baris kredit ke akun bank.' : ($cash ? 'BPV tidak boleh memakai akun kas; gunakan CPV.' : null),
            'BRV' => ! $has(fn ($l, $a) => $a->is_bank && Money::cents($l->debit) > 0) ? 'BRV wajib memiliki baris debit ke akun bank.' : ($cash ? 'BRV tidak boleh memakai akun kas; gunakan CRV.' : null),
            'CPV' => ! $has(fn ($l, $a) => $a->is_cash && Money::cents($l->credit) > 0) ? 'CPV wajib memiliki baris kredit ke akun kas.' : null,
            'CRV' => ! $has(fn ($l, $a) => $a->is_cash && Money::cents($l->debit) > 0) ? 'CRV wajib memiliki baris debit ke akun kas.' : null,
            'RJV' => ($cash || $bank) ? 'RJV tidak boleh memakai akun kas/bank.' : (! $has(fn ($l, $a) => $a->is_receivable && $l->party_id) ? 'RJV wajib memiliki baris akun piutang dengan pihak.' : null),
            'PJV' => ($cash || $bank) ? 'PJV tidak boleh memakai akun kas/bank.' : (! $has(fn ($l, $a) => $a->is_payable && $l->party_id) ? 'PJV wajib memiliki baris akun utang dengan pihak.' : null),
            'MJV', 'AJV' => ($cash || $bank) ? "{$type} tidak boleh memakai akun kas/bank." : null,
            default => 'Jenis voucher tidak dikenal.',
        };
    }

    private function ensurePeriodOpen(Journal $journal): void
    {
        $period = $journal->period ?? FiscalPeriod::find($journal->fiscal_period_id);
        if ($period->isClosed()) {
            throw ValidationException::withMessages(['date' => "Periode {$period->label()} sudah ditutup."]);
        }
    }

    /** Aturan 7: pengeluaran tidak boleh membuat saldo kas/bank minus pada tanggal voucher. */
    private function ensureCashNotNegative(Journal $journal): void
    {
        foreach ($journal->lines->filter(fn ($l) => $l->account->isCashOrBank())->groupBy('coa_account_id') as $accountId => $lines) {
            $net = $lines->sum(fn ($l) => Money::cents($l->debit) - Money::cents($l->credit));
            if ($net >= 0) {
                continue;
            }
            $balance = Ledger::balanceCents((int) $accountId, $journal->date->toDateString());
            if ($balance + $net < 0) {
                $account = $lines->first()->account;
                throw ValidationException::withMessages(['lines' => "Saldo {$account->label()} per {$journal->date->translatedFormat('j M Y')} hanya " . Money::format(Money::decimal($balance)) . '; pengeluaran membuat saldo minus.']);
            }
        }
    }

    /** Aturan 11: {TYPE}/{YYYY}/{MM}/{0001} per tipe per bulan, di dalam lock — tanpa loncatan. */
    private function nextNumber(Journal $journal): string
    {
        $prefix = sprintf('%s/%s/%s/', $journal->voucher_type, $journal->date->format('Y'), $journal->date->format('m'));
        $last = Journal::where('number', 'like', "{$prefix}%")->lockForUpdate()->max('number');

        return $prefix . str_pad((string) ($last ? (int) substr($last, -4) + 1 : 1), 4, '0', STR_PAD_LEFT);
    }

    private function notifyDecision(Journal $journal, string $decision): void
    {
        $users = User::whereIn('id', array_filter([$journal->created_by, $journal->submitted_by]))->where('id', '!=', $journal->approved_by)->get();
        Notification::send($users, new VoucherDecided($journal, $decision));
    }
}
