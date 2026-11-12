<?php

namespace Tests\Feature\M5b;

use App\Models\CoaAccount;
use App\Models\Donation;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\DonationReceived;
use App\Notifications\DonationThanks;
use App\Services\FinancialReports;
use App\Services\JournalPoster;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Donasi publik → verifikasi → voucher → bukti terima; transparansi tanpa nama donatur (PRD 9.15, AC M5b). */
class DonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        Notification::fake();
    }

    private function confirm(array $override = [])
    {
        return $this->post(route('site.donation.send'), array_merge([
            'donor_name' => 'Pak Rahmat Hidayat', 'phone' => '081299990000', 'email' => 'rahmat@example.com', 'amount' => 750000,
            'transferred_at' => today()->toDateString(), 'coa_bank_account_id' => CoaAccount::byCode('11210000')->id, 'purpose' => 'pendidikan',
            'proof' => UploadedFile::fake()->image('transfer.jpg'), 'message' => 'Untuk adik-adik',
        ], $override));
    }

    public function test_public_confirmation_waits_and_does_not_affect_reports()
    {
        $before = FinancialReports::position(today()->toDateString())['totals']['assets'];
        $this->confirm()->assertRedirect(route('site.donation'))->assertSessionHas('success');
        $donation = Donation::where('donor_name', 'Pak Rahmat Hidayat')->firstOrFail();
        $this->assertSame('menunggu', $donation->status);
        $this->assertNull($donation->journal_id);
        Storage::disk('local')->assertExists($donation->proof);
        Notification::assertSentTo(User::where('email', 'pengurus@mail.com')->first(), DonationReceived::class);
        $this->assertSame($before, FinancialReports::position(today()->toDateString())['totals']['assets'], 'donasi menunggu tidak masuk laporan');

        // validasi & honeypot
        $this->confirm(['proof' => null])->assertSessionHasErrors('proof');
        $this->confirm(['donor_name' => '', 'is_anonymous' => 0])->assertSessionHasErrors('donor_name');
        $this->confirm(['coa_bank_account_id' => CoaAccount::byCode('11110000')->id])->assertSessionHasErrors('coa_bank_account_id');
        $count = Donation::count();
        $this->confirm(['website' => 'http://spam'])->assertSessionHas('success');
        $this->assertSame($count, Donation::count());
        // bukti transfer privat
        $this->get(route('donation.proof', $donation))->assertRedirect('/login');
    }

    public function test_verify_creates_exactly_one_voucher_and_receipt_after_posting()
    {
        $this->confirm();
        $donation = Donation::where('donor_name', 'Pak Rahmat Hidayat')->firstOrFail();
        $verifier = User::where('email', 'pengurus@mail.com')->first();
        $treasurer = User::factory()->create()->assignRole('pengurus')->givePackage('bendahara');

        $this->actingAs($verifier)->post(route('donation.verify', $donation))->assertSessionHasNoErrors();
        $this->actingAs($verifier)->post(route('donation.verify', $donation))->assertSessionHasErrors('status');
        $donation->refresh();
        $this->assertSame('terverifikasi', $donation->status);
        $this->assertSame(1, Journal::where('source_type', Donation::class)->where('source_id', $donation->id)->count());
        $journal = $donation->journal;
        $this->assertSame(['BRV', 'diajukan'], [$journal->voucher_type, $journal->status]);
        $this->assertMatchesRegularExpression('/^DON-\d{4}-0001$/', $donation->receipt_number);
        $this->assertSame('Pak Rahmat Hidayat', $donation->party->name);
        $this->assertSame(['11210000', '43000000'], $journal->lines->map(fn ($l) => $l->account->code)->all());

        // pemverifikasi = pengaju → tidak boleh menyetujui; Bendahara lain memposting
        $this->actingAs($verifier)->post(route('journal.post', $journal))->assertForbidden();
        app(JournalPoster::class)->post($journal, $treasurer);
        Notification::assertSentOnDemand(DonationThanks::class);
        $this->actingAs($verifier)->get(route('donation.show', $donation))->assertOk()->assertSee('Bukti terima');

        $url = app(\App\Services\DonationService::class)->receiptUrl($donation->fresh());
        $this->assertStringStartsWith('%PDF', $this->get($url)->assertOk()->getContent());
        $this->get(route('site.donation.receipt', $donation->receipt_number))->assertForbidden(); // tanpa tanda tangan
    }

    public function test_reject_requires_reason_and_anonymous_name_is_hidden()
    {
        $this->confirm(['donor_name' => 'Bu Rahasia', 'is_anonymous' => 1]);
        $donation = Donation::where('donor_name', 'Bu Rahasia')->firstOrFail();
        $this->assertSame('Hamba Allah', $donation->displayName());
        $verifier = User::where('email', 'pengurus@mail.com')->first();
        $this->actingAs($verifier)->post(route('donation.reject', $donation), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($verifier)->post(route('donation.reject', $donation), ['reason' => 'Mutasi tidak ditemukan'])->assertSessionHasNoErrors();
        $this->assertSame(['ditolak', null], [$donation->fresh()->status, $donation->fresh()->journal_id]);
        $this->actingAs($verifier)->post(route('donation.verify', $donation))->assertSessionHasErrors('status');
    }

    public function test_cash_and_goods_donations_and_public_transparency_never_show_donor_names()
    {
        $user = User::where('email', 'pengurus@mail.com')->first();
        $treasurer = User::factory()->create()->assignRole('pengurus')->givePackage('bendahara');
        $this->actingAs($user)->post(route('donation.store'), ['kind' => 'tunai', 'donor_name' => 'Haji Darmawan', 'amount' => 400000, 'transferred_at' => today()->toDateString(),
            'coa_bank_account_id' => CoaAccount::byCode('11110000')->id, 'purpose' => 'pangan', 'verify_now' => 1])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('donation.store'), ['kind' => 'barang', 'donor_name' => 'Toko Sejahtera Abadi', 'amount' => 900000, 'transferred_at' => today()->toDateString(),
            'coa_bank_account_id' => CoaAccount::byCode('11810000')->id, 'purpose' => 'umum', 'item_description' => 'Beras 60 kg', 'verify_now' => 1])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('donation.store'), ['kind' => 'barang', 'donor_name' => 'X', 'amount' => 1, 'transferred_at' => today()->toDateString(),
            'coa_bank_account_id' => CoaAccount::byCode('11110000')->id, 'purpose' => 'umum'])->assertSessionHasErrors('item_description');

        $cash = Donation::where('donor_name', 'Haji Darmawan')->first();
        $goods = Donation::where('donor_name', 'Toko Sejahtera Abadi')->first();
        $this->assertSame(['CRV', 'MJV'], [$cash->journal->voucher_type, $goods->journal->voucher_type]);
        $this->assertSame('49000000', $goods->journal->lines->firstWhere('credit', '>', 0)->account->code);
        foreach ([$cash, $goods] as $d) {
            app(JournalPoster::class)->post($d->journal, $treasurer);
        }

        $page = $this->get(route('site.donation'))->assertOk();
        $page->assertSee('Transparansi')->assertSee(Money::format(1300000))->assertDontSee('Haji Darmawan')->assertDontSee('Toko Sejahtera Abadi')->assertDontSee('Ibu Sari');
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $report = $this->actingAs($admin)->get(route('report.show', 'transparansi'))->assertOk();
        $report->assertDontSee('Haji Darmawan')->assertDontSee('Toko Sejahtera Abadi');

        // terbitkan ke Dokumen publik
        $this->actingAs($admin)->post(route('report.publish-transparency'), ['from' => today()->startOfYear()->toDateString(), 'to' => today()->toDateString()])->assertSessionHas('success');
        $doc = \App\Models\Document::where('category', 'Laporan Transparansi')->firstOrFail();
        $this->assertSame('publik', $doc->visibility);
        Storage::disk('local')->assertExists($doc->file);
    }
}
