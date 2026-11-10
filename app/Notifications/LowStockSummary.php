<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** PRD 9.20: ringkasan harian stok menipis → PIC gudang (lonceng). */
class LowStockSummary extends Notification
{
    use Queueable;

    /** @param array<int, string> $lines "Beras 3 kg (min 10)" */
    public function __construct(public array $lines)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['title' => count($this->lines) . ' barang stok menipis', 'body' => implode(' · ', array_slice($this->lines, 0, 6)), 'url' => route('item.index')];
    }
}
