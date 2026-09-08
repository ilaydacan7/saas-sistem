<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Modules\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => TransactionType::Expense,
            'category_id' => null,
            'amount_minor' => 50000,
            'occurred_on' => now()->toDateString(),
            'description' => null,
            'method' => null,
            'source_type' => null,
            'source_id' => null,
            'created_by_id' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => TransactionType::Income]);
    }
}
