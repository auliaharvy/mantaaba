<?php

namespace App\Notifications;

use App\Models\Donation;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** PRD 9.20: donasi masuk → pemegang donation-verify. */
class DonationReceived extends Notification
{
    use Queueable;

    public function __construct(public Donation $donation)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['title' => 'Konfirmasi donasi masuk', 'body' => $this->donation->displayName() . ' · ' . Money::format($this->donation->amount), 'url' => route('donation.show', $this->donation)];
    }
}
