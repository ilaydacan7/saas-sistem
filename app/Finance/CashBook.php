<?php

declare(strict_types=1);

namespace App\Finance;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Modules\PaymentMethod;
use App\Modules\TransactionType;
use App\Tenancy\TenantContext;
use Illuminate\Support\Carbon;

class CashBook
{
    public function __construct(private readonly TenantContext $context) {}

    /**
     * Finans modülü açıldığında hazır kategori listesini oluşturur.
     * Tekrar çağrılması güvenlidir.
     */
    public function ensureCategories(Tenant $tenant): void
    {
        $this->context->runWithoutTenant(function () use ($tenant): void {
            foreach (TransactionType::cases() as $tur) {
                foreach ($tur->defaultCategories() as $ad) {
                    TransactionCategory::query()->firstOrCreate(
                        ['tenant_id' => $tenant->getKey(), 'type' => $tur->value, 'name' => $ad],
                        ['is_system' => $ad === $tur->systemCategory()]
                    );
                }
            }
        });
    }

    public function record(
        Tenant $tenant,
        TransactionType $type,
        int $amountMinor,
        Carbon $occurredOn,
        ?int $categoryId = null,
        ?string $description = null,
        ?PaymentMethod $method = null,
        ?User $user = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): Transaction {
        if ($amountMinor <= 0) {
            throw new FinanceException('Tutar sıfırdan büyük olmalı.');
        }

        return $this->context->runWithoutTenant(fn () => Transaction::create([
            'tenant_id' => $tenant->getKey(),
            'type' => $type,
            'category_id' => $categoryId,
            'amount_minor' => $amountMinor,
            'occurred_on' => $occurredOn->toDateString(),
            'description' => $description,
            'method' => $method,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'created_by_id' => $user?->getKey(),
        ]));
    }

    /**
     * Bir kaynağa (örneğin satış tahsilatına) bağlı kaydı siler.
     * Kaynak geri alındığında kasadaki karşılığı da kalmamalı.
     */
    public function removeForSource(string $sourceType, int $sourceId): void
    {
        $this->context->runWithoutTenant(function () use ($sourceType, $sourceId): void {
            Transaction::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->delete();
        });
    }

    public function systemCategoryId(Tenant $tenant, TransactionType $type): ?int
    {
        $ad = $type->systemCategory();

        if ($ad === null) {
            return null;
        }

        return $this->context->runWithoutTenant(fn () => TransactionCategory::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('type', $type->value)
            ->where('name', $ad)
            ->value('id'));
    }

    /**
     * Verilen aralıktaki gelir, gider ve net tutar.
     *
     * @return array{gelir: int, gider: int, net: int}
     */
    public function summary(Carbon $baslangic, Carbon $bitis): array
    {
        $gelir = (int) Transaction::query()->income()->between($baslangic, $bitis)->sum('amount_minor');
        $gider = (int) Transaction::query()->expense()->between($baslangic, $bitis)->sum('amount_minor');

        return [
            'gelir' => $gelir,
            'gider' => $gider,
            'net' => $gelir - $gider,
        ];
    }
}
