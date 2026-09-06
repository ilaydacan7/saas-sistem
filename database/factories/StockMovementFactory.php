<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Modules\StockMovementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'type' => StockMovementType::In,
            'quantity' => 10,
            'balance_after' => 10,
            'unit_cost_minor' => null,
            'note' => null,
            'created_by_id' => null,
            'occurred_at' => now(),
        ];
    }
}
