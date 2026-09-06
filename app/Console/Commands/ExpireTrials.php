<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Tenancy\TenantStatus;
use Illuminate\Console\Command;

class ExpireTrials extends Command
{
    protected $signature = 'tenants:expire-trials';

    protected $description = 'Deneme süresi dolmuş kiracıları ödeme bekleyen duruma alır';

    public function handle(): int
    {
        $sayi = Tenant::query()
            ->where('status', TenantStatus::Trialing)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->update(['status' => TenantStatus::PastDue]);

        $this->info($sayi === 0
            ? 'Süresi dolan deneme hesabı yok.'
            : "{$sayi} deneme hesabı ödeme bekliyor durumuna alındı.");

        return self::SUCCESS;
    }
}
