<?php

namespace App\Services;

use App\Models\CoaAccount;
use App\Models\CostCenter;
use App\Models\Donation;
use App\Models\Party;
use App\Models\User;
use App\Notifications\DonationReceived;
use App\Notifications\DonationThanks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/** Verifikasi donasi (PRD 9.15): pihak donatur + tepat satu voucher sistem diajukan (penyetuju ≠ pemverifikasi). */
class DonationService
{
    public function __construct(private JournalPoster $poster)
    {
    }

    public function notifyNew(Donation $donation): void
    {
        Notification::send(User::withPermission('donation-verify'), new DonationReceived($donation));
    }

    public function verify(Donation $donation, User $user, ?int $debitAccountId = null): Donation
    {
        return DB::transaction(function () use ($donation, $user, $debitAccountId) {
            $donation = Donation::whereKey($donation->id)->lockForUpdate()->first();
            if ($donation->status !== 'menunggu' || $donation->journal_id) {
                throw ValidationException::withMessages(['status' => 'Donasi ini sudah diproses.']);
            }
            $account = CoaAccount::find($debitAccountId ?? $donation->coa_bank_account_id);
            [$type, $valid] = match ($donation->kind) {
                'transfer' => ['BRV', $account?->is_bank],
                'tunai' => ['CRV', $account?->is_cash],
                default => ['MJV', $account && ! $account->isCashOrBank() && $account->type === 'aset'],
            };
            if (! $valid) {
                throw ValidationException::withMessages(['coa_bank_account_id' => match ($donation->kind) {
                    'transfer' => 'Pilih rekening bank penerima.', 'tunai' => 'Pilih akun kas penerima.', default => 'Pilih akun persediaan/aset untuk barang donasi.'}]);
            }
            $party = $donation->party ?? $this->partyFor($donation);
            $revenue = $donation->kind === 'barang' ? '49000000' : (Donation::PURPOSES[$donation->purpose][1] ?? '43000000');
            $center = CostCenter::byCode($donation->purpose === 'pangan' ? 'DPR' : ($donation->purpose === 'umum' ? 'UMUM' : 'PND'))?->id;
            $year = $donation->transferred_at->year;
            $last = Donation::where('receipt_number', 'like', "DON-{$year}-%")->lockForUpdate()->max('receipt_number');
            $donation->receipt_number = sprintf('DON-%d-%04d', $year, $last ? (int) substr($last, -4) + 1 : 1);

            $journal = $this->poster->createSystem($type, $donation->transferred_at->toDateString(),
                "Donasi {$donation->receipt_number} · {$donation->purposeLabel()}" . ($donation->kind === 'barang' ? " · {$donation->item_description}" : ''), [
                    ['coa_account_id' => $account->id, 'debit' => $donation->amount, 'memo' => $donation->receipt_number],
                    ['coa_account_id' => CoaAccount::byCode($revenue)->id, 'credit' => $donation->amount, 'cost_center_id' => $center, 'memo' => 'Peruntukan: ' . $donation->purposeLabel()],
                ], $user, $donation->getMorphClass(), $donation->id, [
                    'party_id' => $party->id, 'reference_no' => $donation->receipt_number,
                    'attachments' => $donation->proof ? [['path' => $donation->proof, 'name' => 'bukti-' . $donation->receipt_number . '.' . pathinfo($donation->proof, PATHINFO_EXTENSION)]] : null,
                ]);
            $donation->forceFill(['status' => 'terverifikasi', 'party_id' => $party->id, 'coa_bank_account_id' => $account->id,
                'verified_by' => $user->id, 'verified_at' => now(), 'journal_id' => $journal->id])->save();

            return $donation;
        });
    }

    public function reject(Donation $donation, User $user, string $reason): void
    {
        abort_unless($donation->status === 'menunggu', 422, 'Donasi ini sudah diproses.');
        $donation->forceFill(['status' => 'ditolak', 'reject_reason' => $reason, 'verified_by' => $user->id, 'verified_at' => now()])->save();
    }

    /** Dipanggil JournalPoster setelah voucher donasi terposting: ucapan terima kasih + bukti terima. */
    public function posted(Donation $donation): void
    {
        if ($donation->email) {
            Notification::route('mail', $donation->email)->notify(new DonationThanks($donation, $this->receiptUrl($donation)));
        }
    }

    public function receiptUrl(Donation $donation): string
    {
        return URL::signedRoute('site.donation.receipt', $donation->receipt_number);
    }

    private function partyFor(Donation $donation): Party
    {
        if ($donation->is_anonymous) {
            return Party::firstOrCreate(['name' => 'Hamba Allah', 'type' => 'donatur']);
        }
        $existing = Party::where('type', 'donatur')->where(fn ($q) => $q->when($donation->phone, fn ($p) => $p->orWhere('phone', $donation->phone))
            ->when($donation->email, fn ($p) => $p->orWhere('email', $donation->email)))->when(! $donation->phone && ! $donation->email, fn ($q) => $q->where('name', $donation->donor_name))->first();

        return $existing ?? Party::create(['name' => $donation->donor_name, 'type' => 'donatur', 'phone' => $donation->phone, 'email' => $donation->email]);
    }
}
