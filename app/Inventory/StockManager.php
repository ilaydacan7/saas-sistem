<?php

declare(strict_types=1);

namespace App\Inventory;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\StockMovementType;
use App\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockManager
{
    public function __construct(private readonly TenantContext $context) {}

    /**
     * Stok girişi: satın alma, iade, üretim.
     */
    public function receive(
        Product $product,
        Warehouse $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $note = null,
        ?int $unitCostMinor = null,
        ?Carbon $occurredAt = null,
    ): StockMovement {
        $this->positiveOrFail($quantity);

        return $this->apply($product, $warehouse, StockMovementType::In, $quantity, $user, $note, $unitCostMinor, $occurredAt);
    }

    /**
     * Stok çıkışı: satış, fire, kendi kullanımı.
     */
    public function issue(
        Product $product,
        Warehouse $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $note = null,
        ?Carbon $occurredAt = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        $this->positiveOrFail($quantity);

        return $this->apply(
            $product, $warehouse, StockMovementType::Out, -$quantity, $user, $note,
            null, $occurredAt, $referenceType, $referenceId
        );
    }

    /**
     * Sayım sonucu düzeltme: verilen miktar mevcut stoğun yeni değeridir.
     */
    public function adjust(
        Product $product,
        Warehouse $warehouse,
        float $countedQuantity,
        ?User $user = null,
        ?string $note = null,
        ?Carbon $occurredAt = null,
    ): StockMovement {
        if ($countedQuantity < 0) {
            throw new StockException('Sayım sonucu negatif olamaz.');
        }

        return DB::transaction(function () use ($product, $warehouse, $countedQuantity, $user, $note, $occurredAt) {
            $seviye = $this->lockLevel($product, $warehouse);
            $fark = $countedQuantity - (float) $seviye->quantity;

            return $this->write($product, $warehouse, $seviye, StockMovementType::Adjustment, $fark, $user, $note, null, $occurredAt);
        });
    }

    private function apply(
        Product $product,
        Warehouse $warehouse,
        StockMovementType $type,
        float $delta,
        ?User $user,
        ?string $note,
        ?int $unitCostMinor = null,
        ?Carbon $occurredAt = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $warehouse, $type, $delta, $user, $note, $unitCostMinor, $occurredAt, $referenceType, $referenceId) {
            $seviye = $this->lockLevel($product, $warehouse);

            return $this->write($product, $warehouse, $seviye, $type, $delta, $user, $note, $unitCostMinor, $occurredAt, $referenceType, $referenceId);
        });
    }

    private function write(
        Product $product,
        Warehouse $warehouse,
        StockLevel $seviye,
        StockMovementType $type,
        float $delta,
        ?User $user,
        ?string $note,
        ?int $unitCostMinor = null,
        ?Carbon $occurredAt = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        if (! $product->tracks_stock) {
            throw new StockException($product->name.' için stok takibi kapalı.');
        }

        // Ürün ve depo aynı işletmeye ait olmalı: çağrı yerinde doğrulama
        // atlanırsa bir kiracının deposuna başka kiracının ürünü işlenebilirdi.
        if ($product->tenant_id !== $warehouse->tenant_id) {
            throw new StockException('Ürün ve depo aynı işletmeye ait değil.');
        }

        $yeni = round((float) $seviye->quantity + $delta, 3);

        if ($yeni < 0) {
            throw new StockException(sprintf(
                '%s stoğu yetersiz. Mevcut: %s %s, çıkışı istenen: %s %s.',
                $product->name,
                rtrim(rtrim(number_format((float) $seviye->quantity, 3, ',', '.'), '0'), ','),
                $product->unitLabel(),
                rtrim(rtrim(number_format(abs($delta), 3, ',', '.'), '0'), ','),
                $product->unitLabel(),
            ));
        }

        $seviye->quantity = $yeni;
        $seviye->save();

        return StockMovement::create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'type' => $type,
            'quantity' => $delta,
            'balance_after' => $yeni,
            'unit_cost_minor' => $unitCostMinor,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
            'created_by_id' => $user?->getKey(),
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /**
     * Eşzamanlı iki hareketin aynı bakiyeyi okumasını engellemek için satırı kilitler.
     */
    private function lockLevel(Product $product, Warehouse $warehouse): StockLevel
    {
        return $this->context->runWithoutTenant(function () use ($product, $warehouse): StockLevel {
            StockLevel::query()->firstOrCreate(
                ['product_id' => $product->getKey(), 'warehouse_id' => $warehouse->getKey()],
                ['tenant_id' => $product->tenant_id, 'quantity' => 0]
            );

            return StockLevel::query()
                ->where('product_id', $product->getKey())
                ->where('warehouse_id', $warehouse->getKey())
                ->lockForUpdate()
                ->firstOrFail();
        });
    }

    private function positiveOrFail(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new StockException('Miktar sıfırdan büyük olmalı.');
        }
    }
}
