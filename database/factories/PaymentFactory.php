<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $baslangic = now()->subMonth();

        return [
            'tenant_id' => Tenant::factory(),
            'amount_minor' => 49900,
            'currency' => 'TRY',
            'method' => 'manual',
            'period_starts_at' => $baslangic,
            'period_ends_at' => $baslangic->copy()->addMonth(),
            'recorded_by_id' => null,
            'note' => null,
        ];
    }
}
