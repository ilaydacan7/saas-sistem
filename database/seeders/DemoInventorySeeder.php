<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Inventory\InventoryProvisioner;
use App\Inventory\StockManager;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Modules\Module;
use App\Modules\ProductType;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'acme')->first();

        if ($tenant === null) {
            return;
        }

        $tenant->enableModule(Module::Inventory);

        $context = app(TenantContext::class);
        $stok = app(StockManager::class);
        $depo = app(InventoryProvisioner::class)->defaultWarehouse($tenant);
        $sahip = User::query()->where('tenant_id', $tenant->getKey())->first();

        $context->runFor($tenant, function () use ($tenant, $stok, $depo, $sahip): void {
            $adet = Unit::query()->where('short_name', 'adet')->value('id');
            $kg = Unit::query()->where('short_name', 'kg')->value('id');
            $saat = Unit::query()->where('short_name', 'saat')->value('id');

            $kayitlar = [
                ['Çelik Vida 5mm', 'VD-005', $adet, 1250, 2490, 50, 320],
                ['Ahşap Panel 18mm', 'PN-018', $adet, 24000, 39900, 20, 12],
                ['Yapıştırıcı 1kg', 'YP-001', $kg, 8500, 14900, 15, 4],
                ['Menteşe Takımı', 'MN-100', $adet, 3400, 5900, 30, 0],
            ];

            foreach ($kayitlar as [$ad, $kod, $birim, $alis, $satis, $minimum, $miktar]) {
                $urun = Product::query()->firstOrCreate(
                    ['tenant_id' => $tenant->getKey(), 'sku' => $kod],
                    [
                        'type' => ProductType::Product,
                        'name' => $ad,
                        'unit_id' => $birim,
                        'purchase_price_minor' => $alis,
                        'sale_price_minor' => $satis,
                        'vat_rate' => 20,
                        'tracks_stock' => true,
                        'min_stock' => $minimum,
                        'is_active' => true,
                    ]
                );

                if ($miktar > 0 && $urun->totalStock() <= 0) {
                    $stok->receive($urun, $depo, $miktar, $sahip, 'Açılış stoğu');
                }
            }

            Product::query()->firstOrCreate(
                ['tenant_id' => $tenant->getKey(), 'name' => 'Montaj Hizmeti'],
                [
                    'type' => ProductType::Service,
                    'unit_id' => $saat,
                    'purchase_price_minor' => 0,
                    'sale_price_minor' => 75000,
                    'vat_rate' => 20,
                    'tracks_stock' => false,
                    'min_stock' => 0,
                    'is_active' => true,
                ]
            );
        });
    }
}
