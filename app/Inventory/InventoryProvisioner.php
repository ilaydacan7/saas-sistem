<?php

declare(strict_types=1);

namespace App\Inventory;

use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Tenancy\TenantContext;

class InventoryProvisioner
{
    public function __construct(private readonly TenantContext $context) {}

    /**
     * Stok modülü açıldığında işletmenin çalışmaya hazır olması için gereken
     * asgari kayıtları oluşturur. Tekrar çağrılması güvenlidir.
     *
     * Kiracı bağlamı kurulmadan da çalışır (modül açma, konsol, seeder):
     * hedef kiracı parametreyle geldiği için sorgular açıkça ona kısıtlanır.
     */
    public function ensure(Tenant $tenant): void
    {
        $this->context->runWithoutTenant(function () use ($tenant): void {
            foreach (Unit::defaults() as $birim) {
                Unit::query()->firstOrCreate(
                    ['tenant_id' => $tenant->getKey(), 'short_name' => $birim['short_name']],
                    ['name' => $birim['name'], 'allows_fraction' => $birim['allows_fraction']]
                );
            }
        });

        $this->defaultWarehouse($tenant);
    }

    public function defaultWarehouse(Tenant $tenant): Warehouse
    {
        return $this->context->runWithoutTenant(function () use ($tenant): Warehouse {
            $depo = Warehouse::query()
                ->where('tenant_id', $tenant->getKey())
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

            return $depo ?? Warehouse::query()->create([
                'tenant_id' => $tenant->getKey(),
                'name' => 'Merkez Depo',
                'is_default' => true,
            ]);
        });
    }
}
