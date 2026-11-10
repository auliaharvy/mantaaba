<?php

namespace Tests\Feature\M5a;

use App\Models\CoaAccount;
use App\Models\CostCenter;
use App\Models\FiscalPeriod;
use App\Models\HealthRecord;
use App\Models\Journal;
use App\Models\Member;
use App\Models\Party;
use App\Models\User;
use App\Services\JournalPoster;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Voucher & jurnal double-entry (PRD 9.13, AC M5a). */
class VoucherTest extends TestCase
{
    use RefreshDatabase;

    private User $maker;

    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        $this->maker = User::where('email', 'pengurus@mail.com')->firstOrFail(); // Pengurus Inti: buat & ajukan, tanpa approve
        $this->approver = User::factory()->create()->assignRole('pengurus')->givePackage('bendahara');
    }

    private function acc(string $code): int
    {
        return CoaAccount::byCode($code)->id;
    }

    private function cc(string $code): int
    {
        return CostCenter::byCode($code)->id;
    }

    private function payload(array $override = [], ?array $lines = null): array
    {
        return array_merge([
            'voucher_type' => 'CPV', 'date' => today()->toDateString(), 'description' => 'Belanja bahan dapur',
            'lines' => $lines ?? [
                ['coa_account_id' => $this->acc('1-1401'), 'debit' => 100000, 'cost_center_id' => $this->cc('DPR')],
                ['coa_account_id' => $this->acc('1-1101'), 'credit' => 100000],
            ],
            'attachments' => [UploadedFile::fake()->create('nota.pdf', 50, 'application/pdf')],
        ], $override);
    }

    private function draft(array $lines, string $type = 'CPV', ?string $date = null, array $extra = []): Journal
    {
        return app(JournalPoster::class)->saveDraft(['voucher_type' => $type, 'date' => $date ?? today()->toDateString(), 'description' => 'Uji'] + $extra, $lines, $this->maker)
            ->forceFill(['attachments' => [['path' => 'journals/x.pdf', 'name' => 'x.pdf']]]);
    }

    public function test_full_flow_create_submit_post_with_attachment_and_number()
    {
        $this->actingAs($this->maker)->post(route('journal.store'), $this->payload(['action' => 'submit']))->assertSessionHasNoErrors();
        $journal = Journal::where('description', 'Belanja bahan dapur')->firstOrFail();
        $this->assertSame('diajukan', $journal->status);
        $this->assertNull($journal->number, 'nomor baru diberikan saat posting');
        Storage::disk('local')->assertExists($journal->attachments[0]['path']);
        $this->actingAs($this->approver)->get(route('journal.queue'))->assertOk()->assertSee('Belanja bahan dapur');

        $prefix = sprintf('CPV/%s/', today()->format('Y/m'));
        $expected = $prefix . str_pad((string) (Journal::where('number', 'like', "{$prefix}%")->count() + 1), 4, '0', STR_PAD_LEFT);
        $this->actingAs($this->approver)->post(route('journal.post', $journal))->assertSessionHas('success');
        $journal->refresh();
        $this->assertSame('terposting', $journal->status);
        $this->assertSame($expected, $journal->number);
        $this->assertSame('application/pdf', $this->actingAs($this->maker)->get(route('journal.pdf', $journal))->headers->get('Content-Type'));
    }

    public function test_unbalanced_voucher_shows_difference_and_attachment_is_required()
    {
        $this->actingAs($this->maker)->post(route('journal.store'), $this->payload(['action' => 'submit'], [
            ['coa_account_id' => $this->acc('1-1401'), 'debit' => 100000, 'cost_center_id' => $this->cc('DPR')],
            ['coa_account_id' => $this->acc('1-1101'), 'credit' => 90000],
        ]))->assertSessionHasErrors(['lines' => 'Total debit dan kredit tidak seimbang (selisih Rp 10.000).']);

        $this->actingAs($this->maker)->post(route('journal.store'), $this->payload(['action' => 'submit', 'attachments' => []]))->assertSessionHasErrors('attachments');
        $this->assertSame(2, Journal::where('description', 'Belanja bahan dapur')->where('status', 'draft')->count(), 'draf tetap tersimpan');
        $this->actingAs($this->maker)->postJson(route('journal.store'), $this->payload(['action' => 'submit'], [
            ['coa_account_id' => $this->acc('1-1401'), 'debit' => 100000, 'cost_center_id' => $this->cc('DPR')],
            ['coa_account_id' => $this->acc('1-1101'), 'credit' => 90000],
        ]))->assertStatus(422)->assertJsonFragment(['Total debit dan kredit tidak seimbang (selisih Rp 10.000).']);
    }

    public function test_maker_and_approver_must_differ_even_for_super_admin()
    {
        $admin = User::where('email', 'superadmin@mail.com')->firstOrFail();
        $poster = app(JournalPoster::class);
        $journal = $poster->saveDraft(['voucher_type' => 'CPV', 'date' => today()->toDateString(), 'description' => 'SA'], [
            ['coa_account_id' => $this->acc('5-3100'), 'debit' => 1000, 'cost_center_id' => $this->cc('UMUM')], ['coa_account_id' => $this->acc('1-1101'), 'credit' => 1000]], $admin);
        $journal->forceFill(['attachments' => [['path' => 'x', 'name' => 'x']]])->save();
        $poster->submit($journal, $admin);

        $this->actingAs($admin)->post(route('journal.post', $journal))->assertForbidden();
        $this->actingAs($this->maker)->post(route('journal.post', $journal))->assertForbidden(); // tanpa journal-approve
        $this->actingAs($this->approver)->post(route('journal.post', $journal))->assertSessionHas('success');
    }

    public function test_closed_period_rejects_submit_and_post()
    {
        $journal = $this->draft([['coa_account_id' => $this->acc('1-1401'), 'debit' => 1000, 'cost_center_id' => $this->cc('DPR')], ['coa_account_id' => $this->acc('1-1101'), 'credit' => 1000]]);
        $journal->save();
        app(JournalPoster::class)->submit($journal, $this->maker);
        FiscalPeriod::forDate(today())->update(['status' => 'closed']);

        $this->actingAs($this->approver)->post(route('journal.post', $journal))->assertSessionHasErrors('date');
        $this->assertSame('diajukan', $journal->fresh()->status);
        $other = $this->draft([['coa_account_id' => $this->acc('1-1401'), 'debit' => 1000, 'cost_center_id' => $this->cc('DPR')], ['coa_account_id' => $this->acc('1-1101'), 'credit' => 1000]]);
        $other->save();
        $this->expectException(ValidationException::class);
        app(JournalPoster::class)->submit($other, $this->maker);
    }

    public function test_rules_for_each_of_eight_voucher_types()
    {
        $toko = Party::where('name', 'Toko Berkah')->firstOrFail();
        $expense = ['coa_account_id' => $this->acc('5-3100'), 'debit' => 1000, 'cost_center_id' => $this->cc('UMUM')];
        $income = ['coa_account_id' => $this->acc('4-3100'), 'credit' => 1000, 'cost_center_id' => $this->cc('UMUM')];
        $cases = [
            // [tipe, baris tidak valid, baris valid]
            'BPV' => [[$expense, ['coa_account_id' => $this->acc('1-1101'), 'credit' => 1000]], [$expense, ['coa_account_id' => $this->acc('1-1201'), 'credit' => 1000]]],
            'BRV' => [[['coa_account_id' => $this->acc('1-1101'), 'debit' => 1000], $income], [['coa_account_id' => $this->acc('1-1201'), 'debit' => 1000], $income]],
            'CPV' => [[$expense, ['coa_account_id' => $this->acc('1-1201'), 'credit' => 1000]], [$expense, ['coa_account_id' => $this->acc('1-1101'), 'credit' => 1000]]],
            'CRV' => [[['coa_account_id' => $this->acc('1-1201'), 'debit' => 1000], $income], [['coa_account_id' => $this->acc('1-1101'), 'debit' => 1000], $income]],
            'RJV' => [[['coa_account_id' => $this->acc('1-1101'), 'debit' => 1000], $income], [['coa_account_id' => $this->acc('1-1301'), 'debit' => 1000, 'party_id' => $toko->id], $income]],
            'PJV' => [[$expense, ['coa_account_id' => $this->acc('1-1201'), 'credit' => 1000]], [$expense, ['coa_account_id' => $this->acc('2-1100'), 'credit' => 1000, 'party_id' => $toko->id]]],
            'MJV' => [[$expense, ['coa_account_id' => $this->acc('1-1101'), 'credit' => 1000]], [$expense, ['coa_account_id' => $this->acc('1-1404'), 'credit' => 1000]]],
            'AJV' => [[$expense, ['coa_account_id' => $this->acc('1-1201'), 'credit' => 1000]], [['coa_account_id' => $this->acc('5-2300'), 'debit' => 1000, 'cost_center_id' => $this->cc('UMUM')], ['coa_account_id' => $this->acc('1-2904'), 'credit' => 1000]]],
        ];
        $poster = app(JournalPoster::class);
        foreach ($cases as $type => [$invalid, $valid]) {
            try {
                $poster->validate($this->draftAs($type, $invalid));
                $this->fail("{$type} tidak valid seharusnya ditolak");
            } catch (ValidationException $e) {
                $this->assertStringContainsString($type, implode(' ', $e->errors()['lines']), "{$type}: pesan menyebut jenis voucher");
            }
            $poster->validate($this->draftAs($type, $valid));
        }
        // AJV hanya untuk penyetuju jurnal
        $this->actingAs($this->maker)->post(route('journal.store'), $this->payload(['voucher_type' => 'AJV']))->assertSessionHasErrors('voucher_type');
    }

    private function draftAs(string $type, array $lines): Journal
    {
        $poster = app(JournalPoster::class);
        $user = $type === 'AJV' ? $this->approver : $this->maker;

        return $poster->saveDraft(['voucher_type' => $type, 'date' => today()->toDateString(), 'description' => "Uji {$type}"], $lines, $user);
    }

    public function test_account_party_and_cost_center_rules()
    {
        $poster = app(JournalPoster::class);
        $cases = [
            'header' => [['coa_account_id' => $this->acc('1-1100'), 'debit' => 1000], ['coa_account_id' => $this->acc('1-1404'), 'credit' => 1000]],
            'piutang tanpa pihak' => [['coa_account_id' => $this->acc('1-1302'), 'debit' => 1000], ['coa_account_id' => $this->acc('1-1404'), 'credit' => 1000]],
            'beban tanpa pusat biaya' => [['coa_account_id' => $this->acc('5-3100'), 'debit' => 1000], ['coa_account_id' => $this->acc('1-1404'), 'credit' => 1000]],
            'debit dan kredit di satu baris' => [['coa_account_id' => $this->acc('1-1401'), 'debit' => 1000, 'credit' => 1000], ['coa_account_id' => $this->acc('1-1404'), 'credit' => 1000]],
        ];
        foreach ($cases as $label => $lines) {
            try {
                $poster->validate($this->draftAs('MJV', $lines));
                $this->fail("{$label} seharusnya ditolak");
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        CoaAccount::byCode('1-1404')->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        $poster->validate($this->draftAs('MJV', [['coa_account_id' => $this->acc('1-1401'), 'debit' => 1000], ['coa_account_id' => $this->acc('1-1404'), 'credit' => 1000]]));
    }

    public function test_posted_voucher_is_immutable_and_reversal_restores_balance()
    {
        $kas = $this->acc('1-1101');
        $before = Ledger::balanceCents($kas, today()->toDateString());
        $journal = $this->draft([['coa_account_id' => $this->acc('5-3100'), 'debit' => 250000, 'cost_center_id' => $this->cc('UMUM')], ['coa_account_id' => $kas, 'credit' => 250000]]);
        $journal->save();
        app(JournalPoster::class)->submit($journal, $this->maker);
        app(JournalPoster::class)->post($journal, $this->approver);
        $this->assertSame($before - 25000000, Ledger::balanceCents($kas, today()->toDateString()));

        $this->actingAs($this->maker)->get(route('journal.edit', $journal))->assertForbidden();
        $this->actingAs($this->maker)->patch(route('journal.update', $journal), $this->payload())->assertForbidden();
        $this->actingAs($this->maker)->delete(route('journal.destroy', $journal))->assertForbidden();
        $this->actingAs(User::where('email', 'superadmin@mail.com')->first())->delete(route('journal.destroy', $journal))->assertForbidden();

        $this->actingAs($this->approver)->post(route('journal.reverse', $journal))->assertRedirect();
        $this->assertSame('dibalik', $journal->fresh()->status);
        $this->assertSame($before, Ledger::balanceCents($kas, today()->toDateString()));
        $reversal = $journal->fresh()->reversal;
        $this->assertSame('terposting', $reversal->status);
        $this->actingAs($this->approver)->post(route('journal.reverse', $journal))->assertStatus(422);
    }

    public function test_numbers_have_no_gaps_when_drafts_are_rejected_or_deleted()
    {
        $poster = app(JournalPoster::class);
        $lines = fn () => [['coa_account_id' => $this->acc('1-1401'), 'debit' => 1000, 'cost_center_id' => $this->cc('DPR')], ['coa_account_id' => $this->acc('1-1101'), 'credit' => 1000]];
        $make = function () use ($poster, $lines) {
            $j = $this->draft($lines());
            $j->save();

            return $poster->submit($j, $this->maker);
        };
        [$a, $b, $c, $d] = [$make(), $make(), $make(), $make()];
        $poster->reject($b, $this->approver, 'Nota buram');
        $this->actingAs($this->maker)->delete(route('journal.destroy', $b))->assertNoContent();
        $poster->post($c, $this->approver);
        $poster->post($a, $this->approver);
        $poster->post($d, $this->approver);
        $prefix = 'CPV/' . today()->format('Y/m') . '/';
        // seeder memposting 1 CPV bulan ini bila tanggalnya jatuh di bulan yang sama
        $numbers = Journal::where('number', 'like', "{$prefix}%")->orderBy('number')->pluck('number')->map(fn ($n) => (int) substr($n, -4))->all();
        $this->assertSame(range(1, count($numbers)), $numbers);
    }

    public function test_cash_cannot_go_negative()
    {
        $kas = $this->acc('1-1102');
        $balance = Ledger::balanceCents($kas, today()->toDateString()) / 100;
        $journal = $this->draft([['coa_account_id' => $this->acc('5-1200'), 'debit' => $balance + 1, 'cost_center_id' => $this->cc('DPR')], ['coa_account_id' => $kas, 'credit' => $balance + 1]]);
        $journal->save();
        app(JournalPoster::class)->submit($journal, $this->maker);
        $this->actingAs($this->approver)->post(route('journal.post', $journal))->assertSessionHasErrors('lines');
        $this->assertSame('diajukan', $journal->fresh()->status);
    }

    public function test_random_balanced_vouchers_keep_trial_balance_balanced()
    {
        $poster = app(JournalPoster::class);
        $expenses = ['5-1100', '5-1200', '5-1300', '5-3100', '5-3200'];
        mt_srand(2026);
        for ($i = 0; $i < 25; $i++) {
            $amount = mt_rand(1, 5000) * 100 + mt_rand(0, 99) / 100;
            $split = round($amount * mt_rand(1, 9) / 10, 2);
            $lines = [
                ['coa_account_id' => $this->acc($expenses[array_rand($expenses)]), 'debit' => $split, 'cost_center_id' => $this->cc('UMUM')],
                ['coa_account_id' => $this->acc($expenses[array_rand($expenses)]), 'debit' => round($amount - $split, 2), 'cost_center_id' => $this->cc('PND')],
                ['coa_account_id' => $this->acc('1-1201'), 'credit' => $amount],
            ];
            $j = $this->draft(array_values(array_filter($lines, fn ($l) => ($l['debit'] ?? 1) > 0)), 'BPV');
            $j->save();
            $poster->post($poster->submit($j, $this->maker), $this->approver);
        }
        $rows = Ledger::trialBalance(today()->toDateString());
        $this->assertSame($rows->sum('debit'), $rows->sum('credit'));
        $this->actingAs($this->approver)->get(route('ledger.trial-balance'))->assertOk()->assertSee('Seimbang');
    }

    public function test_opening_balance_wizard_requires_zero_difference_and_runs_once()
    {
        Journal::query()->delete();
        $admin = User::where('email', 'superadmin@mail.com')->firstOrFail();
        $payload = ['date' => today()->startOfYear()->toDateString(), 'amounts' => [$this->acc('1-1101') => 1000000, $this->acc('3-1100') => 900000]];
        $this->actingAs($admin)->post(route('opening.store'), $payload)->assertSessionHasErrors('lines');
        $payload['amounts'][$this->acc('3-1100')] = 800000;
        $payload['party_rows'] = [['coa_account_id' => $this->acc('2-1100'), 'party_id' => Party::first()->id, 'amount' => 200000]];
        $this->actingAs($admin)->post(route('opening.store'), $payload)->assertSessionHasNoErrors();
        $opening = Journal::where('source_type', 'opening_balance')->firstOrFail();
        $this->assertSame('terposting', $opening->status);
        $this->assertSame(100000000, Ledger::balanceCents($this->acc('1-1101'), today()->toDateString()));
        $this->actingAs($admin)->post(route('opening.store'), $payload)->assertStatus(422);
        $this->actingAs($this->maker)->get(route('opening.show'))->assertForbidden();
    }

    public function test_health_cost_creates_voucher_draft_and_master_rules()
    {
        $member = Member::first();
        $health = HealthRecord::create(['member_id' => $member->id, 'date' => today(), 'complaint' => 'Demam', 'action' => 'klinik', 'cost' => 75000]);
        $this->actingAs($this->maker)->post(route('journal.from-health', $health))->assertRedirect();
        $journal = Journal::where('source_id', $health->id)->where('source_type', $health->getMorphClass())->firstOrFail();
        $this->assertSame(['CPV', 'draft'], [$journal->voucher_type, $journal->status]);
        $this->assertEquals(75000, $journal->lines->firstWhere('coa_account_id', $this->acc('5-1500'))->debit);
        $this->actingAs($this->maker)->post(route('journal.from-health', $health))->assertSessionHas('warning');

        // akun yang sudah dipakai jurnal tidak bisa diubah tipenya
        $kas = CoaAccount::byCode('1-1101');
        $this->actingAs(User::where('email', 'superadmin@mail.com')->first())->patch(route('coa.update', $kas), ['code' => '1-1101', 'name' => 'Kas', 'type' => 'beban'])->assertSessionHasErrors('type');
        $this->actingAs(User::where('email', 'superadmin@mail.com')->first())->delete(route('coa.destroy', $kas))->assertStatus(422);

        // buka ulang periode hanya Super Admin dengan alasan
        $period = FiscalPeriod::forDate(today());
        $this->actingAs($this->maker)->post(route('period.close', $period))->assertForbidden(); // Pengurus Inti tanpa period-close
        $this->actingAs(User::where('email', 'superadmin@mail.com')->first())->post(route('period.close', $period))->assertSessionHas('success');
        $this->actingAs(User::where('email', 'superadmin@mail.com')->first())->post(route('period.reopen', $period), [])->assertSessionHasErrors('reason');
        $this->actingAs(User::where('email', 'superadmin@mail.com')->first())->post(route('period.reopen', $period), ['reason' => 'Koreksi nota'])->assertSessionHas('success');

        // unit bisnis baru mendapat pusat biaya otomatis
        $unit = \App\Models\BusinessUnit::create(['name' => 'Mantaaba Katering', 'type' => 'unit_bisnis']);
        $this->assertSame('Mantaaba Katering', CostCenter::where('business_unit_id', $unit->id)->value('name'));
    }
}
