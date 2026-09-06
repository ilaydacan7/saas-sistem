<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Tenancy\TenantStatus;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'tenants:expire-subscriptions';

    protected $description = 'Ödenmiş dönemi biten kiracıları ödeme bekleyen duruma alır';

    public function handle(): int
    {
        $sayi = Tenant::query()
            ->where('status', TenantStatus::Active)
            ->whereNotNull('paid_until')
            ->where('paid_until', '<=', now())
            ->update(['status' => TenantStatus::PastDue]);

        $this->info($sayi === 0
            ? 'Dönemi biten abonelik yok.'
            : "{$sayi} abonelik ödeme bekliyor durumuna alındı.");

        return self::SUCCESS;
    }
}
