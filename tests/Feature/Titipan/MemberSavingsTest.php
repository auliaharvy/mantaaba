<?php

namespace Tests\Feature\Titipan;

use App\Models\CoaAccount;
use App\Models\Journal;
use App\Models\Member;
use App\Models\Party;
use App\Models\User;
use App\Services\JournalPoster;
use App\Services\MemberSavings;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Titipan/tabungan anggota (21500000 per pihak anggota) & format nomor voucher COA Mantaaba. */
class MemberSavingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_every_member_gets_a_party_named_after_member_number()
    {
        $member = Member::create(['member_number' => 'MTB-20269999', 'name' => 'Uji Titipan', 'category' => 'siswa', 'status' => 'aktif']);
        $party = $member->party;
        $this->assertSame(['MTB-20269999', 'anggota', 'Uji Titipan'], [$party->code, $party->type, $party->name]);
        $member->update(['name' => 'Uji Titipan Baru']);
        $this->assertSame('Uji Titipan Baru', $party->fresh()->name);
        $this->assertSame(Member::count(), Party::whereNotNull('member_id')->count());
    }

    public function test_member_sees_only_own_savings_balance_and_history()
    {
        $user = User::where('email', 'anggota@mail.com')->first();
        // demo: setor 250.000, ambil 50.000 (terposting)
        $statement = MemberSavings::statement($user->member);
        $this->assertSame(Money::cents(200000), $statement['balance']);
        $this->assertCount(2, $statement['rows']);
        $this->assertSame(Money::cents(50000), $statement['rows'][0]['out'], 'terbaru di atas');

        // setoran yang belum diposting tidak ikut dihitung
        $poster = app(JournalPoster::class);
        $maker = User::where('email', 'pengurus@mail.com')->first();
        $party = $user->member->party;
        $journal = $poster->saveDraft(['voucher_type' => 'CRV', 'date' => today()->toDateString(), 'description' => 'Setor titipan'], [
            ['coa_account_id' => CoaAccount::byCode('11110000')->id, 'debit' => 100000],
            ['coa_account_id' => CoaAccount::byCode('21500000')->id, 'credit' => 100000, 'party_id' => $party->id],
        ], $maker);
        $journal->forceFill(['attachments' => [['path' => 'journals/x.pdf', 'name' => 'x.pdf']]])->save();
        $poster->submit($journal, $maker);
        $this->assertSame(Money::cents(200000), MemberSavings::statement($user->member)['balance']);
        $poster->post($journal, User::where('email', 'superadmin@mail.com')->first());
        $this->assertSame(Money::cents(300000), MemberSavings::statement($user->member->fresh())['balance']);

        $this->actingAs($user)->get(route('my-savings.index'))->assertOk()->assertSee('Rp 300.000')->assertSee('21500000-' . $party->code)->assertSee('Setoran titipan uang saku');
        $this->actingAs($user)->get(route('overview.show', 'saya'))->assertOk()->assertSee('Saldo titipan saya');

        // anggota lain hanya melihat titipannya sendiri (saldo 0)
        $other = Member::where('id', '!=', $user->member->id)->where('category', 'siswa')->first();
        $otherUser = User::factory()->create();
        $other->update(['user_id' => $otherUser->id]);
        $this->actingAs($otherUser)->get(route('my-savings.index'))->assertOk()->assertSee('Rp 0')->assertDontSee('Setoran titipan uang saku');
        // pengguna tanpa data anggota → 404
        $this->actingAs(User::where('email', 'superadmin@mail.com')->first())->get(route('my-savings.index'))->assertNotFound();
    }

    public function test_voucher_number_follows_mantaaba_format()
    {
        $numbers = Journal::whereNotNull('number')->pluck('number');
        $this->assertNotEmpty($numbers);
        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^(BPV|BRV|CPV|CRV|RJV|PJV|MJV|AJV)-\d{8}-\d{3}$/', $number);
        }
        $journal = Journal::whereNotNull('number')->first();
        $this->assertStringContainsString($journal->date->format('dmY'), $journal->number);
    }
}
