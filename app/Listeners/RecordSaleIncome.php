<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SalePaymentRecorded;
use App\Finance\CashBook;
use App\Models\SalePayment;
use App\Models\Tenant;
use App\Modules\Module;
use App\Modules\TransactionType;
use App\Tenancy\TenantContext;

class RecordSaleIncome
{
    public function __construct(
        private readonly CashBook $kasa,
        private readonly TenantContext $context,
    ) {}

    public function handle(SalePaymentRecorded $olay): void
    {
        $tahsilat = $olay->payment;

        $tenant = $this->context->runWithoutTenant(
            fn () => Tenant::query()->find($tahsilat->tenant_id)
        );

        if ($tenant === null || ! $tenant->hasModule(Module::Finance)) {
            return;
        }

        $this->kasa->ensureCategories($tenant);

        $sale = $this->context->runWithoutTenant(fn () => $tahsilat->sale);

        $this->kasa->record(
            tenant: $tenant,
            type: TransactionType::Income,
            amountMinor: $tahsilat->amount_minor,
            occurredOn: $tahsilat->paid_at,
            categoryId: $this->kasa->systemCategoryId($tenant, TransactionType::Income),
            description: $sale !== null ? $sale->number.' tahsilatı' : 'Satış tahsilatı',
            method: $tahsilat->method,
            user: null,
            sourceType: SalePayment::class,
            sourceId: $tahsilat->getKey(),
        );
    }
}
