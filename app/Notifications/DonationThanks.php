<?php

namespace App\Notifications;

use App\Models\Donation;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 9.15: terima kasih + bukti terima donasi bernomor. */
class DonationThanks extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Donation $donation, public string $receiptUrl)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Terima kasih atas donasi Anda · ' . config('app.name'))
            ->greeting('Assalamu\'alaikum, ' . $this->donation->displayName())
            ->line('Jazakumullahu khairan. Donasi ' . Money::format($this->donation->amount) . ' untuk ' . $this->donation->purposeLabel() . ' telah kami terima dan catat.')
            ->line("Nomor bukti terima: {$this->donation->receipt_number}")
            ->action('Unduh bukti terima', $this->receiptUrl)
            ->line('Laporan penggunaan dana dipublikasikan di halaman Dokumen situs kami.');
    }
}
