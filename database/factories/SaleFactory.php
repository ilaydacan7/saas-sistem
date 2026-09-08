<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Tenant;
use App\Modules\SaleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'number' => 'SAT-'.fake()->unique()->numerify('######'),
            'customer_id' => null,
            'warehouse_id' => null,
            'status' => SaleStatus::Confirmed,
            'subtotal_minor' => 10000,
            'discount_minor' => 0,
            'vat_minor' => 2000,
            'total_minor' => 12000,
            'paid_minor' => 0,
            'sold_at' => now(),
            'due_on' => null,
            'note' => null,
            'created_by_id' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $nitelikler) => ['paid_minor' => $nitelikler['total_minor']]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => SaleStatus::Cancelled]);
    }
}
