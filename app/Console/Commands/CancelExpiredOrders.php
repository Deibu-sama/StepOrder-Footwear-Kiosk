<?php

namespace App\Console\Commands;

use App\Services\PendingOrderService;
use App\Services\SettingsService;
use Illuminate\Console\Command;

class CancelExpiredOrders extends Command
{
    protected $signature = 'orders:cancel-expired {--days= : Override the pending-order expiry period}';
    protected $description = 'Automatically cancel unpaid pending orders after the configured expiry period and restore reserved stock.';

    public function handle(PendingOrderService $pendingOrders, SettingsService $settings): int
    {
        $configuredDays = (int)($settings->all()['pending_order_expiry_days'] ?? 7);
        $days = max(1, (int)($this->option('days') ?: $configuredDays));

        $cancelled = $pendingOrders->cancelExpired($days);

        $this->info("Cancelled {$cancelled} expired pending order(s).");

        return self::SUCCESS;
    }
}
