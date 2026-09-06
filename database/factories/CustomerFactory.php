<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use App\Modules\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => CustomerType::Individual,
            'name' => fake()->name(),
            'company_name' => null,
            'tax_office' => null,
            'tax_number' => null,
            'phone' => fake()->numerify('05## ### ## ##'),
            'email' => fake()->unique()->safeEmail(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'note' => null,
            'is_active' => true,
            'created_by_id' => null,
        ];
    }

    public function company(): static
    {
        return $this->state(fn () => [
            'type' => CustomerType::Company,
            'company_name' => fake()->company().' Ltd. Şti.',
            'tax_office' => fake()->city().' VD',
            'tax_number' => fake()->numerify('##########'),
        ]);
    }

    public function passive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
