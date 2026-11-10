<?php

namespace App\Notifications;

use App\Models\Journal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** PRD 9.20: voucher terposting / ditolak → pembuat & pengaju. */
class VoucherDecided extends Notification
{
    use Queueable;

    public function __construct(public Journal $journal, public string $decision)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return $this->decision === 'posted'
            ? ['title' => 'Voucher terposting', 'body' => "{$this->journal->number} · {$this->journal->description}", 'url' => route('journal.show', $this->journal)]
            : ['title' => 'Voucher ditolak', 'body' => "{$this->journal->description} · {$this->journal->reject_reason}", 'url' => route('journal.show', $this->journal)];
    }
}
