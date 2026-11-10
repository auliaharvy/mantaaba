<?php

namespace App\Notifications;

use App\Models\Journal;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** PRD 9.20: voucher diajukan → pemegang journal-approve. */
class VoucherSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Journal $journal)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['title' => "Voucher {$this->journal->voucher_type} menunggu persetujuan", 'body' => $this->journal->description . ' · ' . Money::format($this->journal->total()), 'url' => route('journal.show', $this->journal)];
    }
}
