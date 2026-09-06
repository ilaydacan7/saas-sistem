<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use App\Modules\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => ProductType::Product,
            'sku' => strtoupper(fake()->unique()->bothify('???-####')),
            'name' => ucfirst(fake()->words(2, true)),
            'unit_id' => null,
            'purchase_price_minor' => 10000,
            'sale_price_minor' => 15000,
            'vat_rate' => 20,
            'tracks_stock' => true,
            'min_stock' => 5,
            'is_active' => true,
            'note' => null,
        ];
    }

    public function service(): static
    {
        return $this->state(fn () => [
            'type' => ProductType::Service,
            'tracks_stock' => false,
            'min_stock' => 0,
            'sku' => null,
        ]);
    }

    public function passive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
