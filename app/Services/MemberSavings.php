<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Member;
use App\Support\Money;
use App\Support\PostingRules;

/** Titipan/tabungan anggota = saldo akun Utang Titipan Anggota (21500000) per pihak anggota, hanya voucher terposting. */
class MemberSavings
{
    /** Mutasi & saldo (sen): setoran = kredit (masuk), pengambilan = debit (keluar). */
    public static function statement(Member $member): array
    {
        $account = PostingRules::account('member_savings.account');
        $party = $member->party;
        $lines = $party ? JournalLine::with('journal')->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.coa_account_id', $account->id)->where('journal_lines.party_id', $party->id)->whereIn('journals.status', Journal::POSTED)
            ->orderBy('journals.date')->orderBy('journals.id')->select('journal_lines.*')->get() : collect();
        $balance = 0;
        $rows = $lines->map(function (JournalLine $line) use (&$balance) {
            $in = Money::cents($line->credit);
            $out = Money::cents($line->debit);
            $balance += $in - $out;

            return ['date' => $line->journal->date, 'number' => $line->journal->number, 'description' => $line->memo ?: $line->journal->description, 'in' => $in, 'out' => $out, 'balance' => $balance];
        });

        return ['account' => $account, 'party' => $party, 'rows' => $rows->reverse()->values(), 'balance' => $balance];
    }
}
