<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\LowStockSummary;
use App\Services\StockLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/** Ringkasan harian stok menipis ke PIC gudang; bila tidak ada PIC, ke pemegang stock-update. */
class NotifyLowStock extends Command
{
    protected $signature = 'stock:low-summary';

    protected $description = 'Kirim ringkasan stok di bawah minimum ke PIC gudang';

    public function handle(): int
    {
        $low = Item::lowStock();
        if ($low->isEmpty()) {
            return self::SUCCESS;
        }
        $lines = $low->map(fn ($i) => "{$i->name} " . StockLedger::qty($i->balance) . " {$i->unit} (min " . StockLedger::qty((float) $i->min_stock) . ')')->all();
        $recipients = User::whereIn('id', Warehouse::whereNotNull('pic_user_id')->pluck('pic_user_id'))->where('is_active', true)->get();
        Notification::send($recipients->isNotEmpty() ? $recipients : User::withPermission('stock-update'), new LowStockSummary($lines));
        $this->info(count($lines) . ' barang menipis dilaporkan.');

        return self::SUCCESS;
    }
}
