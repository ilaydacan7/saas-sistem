<?php

declare(strict_types=1);

namespace App\Sales;

use App\Inventory\StockManager;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SalePayment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Modules\PaymentMethod;
use App\Modules\SaleStatus;
use App\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleRecorder
{
    public function __construct(
        private readonly StockManager $stok,
        private readonly TenantContext $context,
    ) {}

    /**
     * @param  list<array{product_id: ?int, name: string, quantity: float, unit_price_minor: int, vat_rate: int}>  $satirlar
     */
    public function record(
        Tenant $tenant,
        array $satirlar,
        ?int $customerId = null,
        ?Warehouse $warehouse = null,
        int $discountMinor = 0,
        ?Carbon $soldAt = null,
        ?Carbon $dueOn = null,
        ?string $note = null,
        ?User $user = null,
        int $initialPaymentMinor = 0,
        PaymentMethod $method = PaymentMethod::Cash,
    ): Sale {
        if ($satirlar === []) {
            throw new SaleException('Satışta en az bir satır olmalı.');
        }

        return DB::transaction(function () use (
            $tenant, $satirlar, $customerId, $warehouse, $discountMinor,
            $soldAt, $dueOn, $note, $user, $initialPaymentMinor, $method
        ) {
            $hesap = $this->hesapla($satirlar, $discountMinor);

            $sale = Sale::create([
                'tenant_id' => $tenant->getKey(),
                'number' => $this->siradakiNumara($tenant),
                'customer_id' => $customerId,
                'warehouse_id' => $warehouse?->getKey(),
                'status' => SaleStatus::Confirmed,
                'subtotal_minor' => $hesap['ara_toplam'],
                'discount_minor' => $hesap['indirim'],
                'vat_minor' => $hesap['kdv'],
                'total_minor' => $hesap['toplam'],
                'paid_minor' => 0,
                'sold_at' => $soldAt ?? now(),
                'due_on' => $dueOn,
                'note' => $note,
                'created_by_id' => $user?->getKey(),
            ]);

            foreach ($satirlar as $satir) {
                $urun = $satir['product_id'] !== null
                    ? Product::query()->find($satir['product_id'])
                    : null;

                SaleLine::create([
                    'tenant_id' => $tenant->getKey(),
                    'sale_id' => $sale->getKey(),
                    'product_id' => $urun?->getKey(),
                    'name' => $satir['name'],
                    'quantity' => $satir['quantity'],
                    'unit_label' => $urun?->unitLabel(),
                    'unit_price_minor' => $satir['unit_price_minor'],
                    'vat_rate' => $satir['vat_rate'],
                    'line_total_minor' => $this->satirToplami($satir),
                ]);

                if ($urun !== null && $urun->tracks_stock && $warehouse !== null) {
                    $this->stok->issue(
                        product: $urun,
                        warehouse: $warehouse,
                        quantity: $satir['quantity'],
                        user: $user,
                        note: 'Satış '.$sale->number,
                        referenceType: Sale::class,
                        referenceId: $sale->getKey(),
                    );
                }
            }

            if ($initialPaymentMinor > 0) {
                $this->addPayment($sale, $initialPaymentMinor, $method, $user);
            }

            return $sale->fresh(['lines', 'payments']);
        });
    }

    public function addPayment(
        Sale $sale,
        int $amountMinor,
        PaymentMethod $method = PaymentMethod::Cash,
        ?User $user = null,
        ?Carbon $paidAt = null,
        ?string $note = null,
    ): SalePayment {
        if ($amountMinor <= 0) {
            throw new SaleException('Tahsilat tutarı sıfırdan büyük olmalı.');
        }

        if ($sale->status === SaleStatus::Cancelled) {
            throw new SaleException('İptal edilmiş satışa tahsilat işlenemez.');
        }

        if ($amountMinor > $sale->remainingMinor()) {
            throw new SaleException(sprintf(
                'Kalan borç %s. Daha fazla tahsilat işlenemez.',
                $this->paraMetni($sale->remainingMinor()),
            ));
        }

        return DB::transaction(function () use ($sale, $amountMinor, $method, $user, $paidAt, $note) {
            $payment = SalePayment::create([
                'tenant_id' => $sale->tenant_id,
                'sale_id' => $sale->getKey(),
                'amount_minor' => $amountMinor,
                'method' => $method,
                'paid_at' => $paidAt ?? now(),
                'note' => $note,
                'created_by_id' => $user?->getKey(),
            ]);

            $sale->forceFill(['paid_minor' => $sale->paid_minor + $amountMinor])->save();

            return $payment;
        });
    }

    /**
     * Satışı iptal eder ve stoğu geri yükler.
     */
    public function cancel(Sale $sale, ?User $user = null): void
    {
        if ($sale->status === SaleStatus::Cancelled) {
            return;
        }

        if ($sale->paid_minor > 0) {
            throw new SaleException('Tahsilat yapılmış satış iptal edilemez. Önce tahsilatı geri alın.');
        }

        DB::transaction(function () use ($sale, $user): void {
            $depo = $sale->warehouse;

            if ($depo !== null) {
                foreach ($sale->lines()->with('product')->get() as $satir) {
                    if ($satir->product !== null && $satir->product->tracks_stock) {
                        $this->stok->receive(
                            product: $satir->product,
                            warehouse: $depo,
                            quantity: (float) $satir->quantity,
                            user: $user,
                            note: 'Satış iptali '.$sale->number,
                        );
                    }
                }
            }

            $sale->forceFill(['status' => SaleStatus::Cancelled])->save();
        });
    }

    /**
     * @param  list<array{quantity: float, unit_price_minor: int, vat_rate: int}>  $satirlar
     * @return array{ara_toplam: int, indirim: int, kdv: int, toplam: int}
     */
    private function hesapla(array $satirlar, int $indirim): array
    {
        $araToplam = 0;
        $kdv = 0;

        foreach ($satirlar as $satir) {
            $satirToplami = $this->satirToplami($satir);
            $araToplam += $satirToplami;
            $kdv += (int) round($satirToplami * $satir['vat_rate'] / 100);
        }

        $indirim = min($indirim, $araToplam);

        // İndirim ara toplama uygulanır; KDV indirim oranında azalır.
        if ($indirim > 0 && $araToplam > 0) {
            $kdv = (int) round($kdv * (($araToplam - $indirim) / $araToplam));
        }

        return [
            'ara_toplam' => $araToplam,
            'indirim' => $indirim,
            'kdv' => $kdv,
            'toplam' => $araToplam - $indirim + $kdv,
        ];
    }

    /**
     * @param  array{quantity: float, unit_price_minor: int}  $satir
     */
    private function satirToplami(array $satir): int
    {
        return (int) round($satir['quantity'] * $satir['unit_price_minor']);
    }

    /**
     * İşletme başına sıralı belge numarası: SAT-2026-0001
     */
    private function siradakiNumara(Tenant $tenant): string
    {
        return $this->context->runWithoutTenant(function () use ($tenant): string {
            $yil = now()->year;
            $onEk = 'SAT-'.$yil.'-';

            $sonuncu = Sale::withTrashed()
                ->where('tenant_id', $tenant->getKey())
                ->where('number', 'like', $onEk.'%')
                ->orderByDesc('number')
                ->value('number');

            $sira = $sonuncu === null ? 1 : ((int) substr($sonuncu, strlen($onEk))) + 1;

            return $onEk.str_pad((string) $sira, 4, '0', STR_PAD_LEFT);
        });
    }

    private function paraMetni(int $kurus): string
    {
        return number_format($kurus / 100, 2, ',', '.').' '.config('billing.currency');
    }
}
