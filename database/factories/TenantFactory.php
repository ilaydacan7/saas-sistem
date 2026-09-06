<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Tenancy\TenantStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'custom_domain' => null,
            'status' => TenantStatus::Active,
            'trial_ends_at' => null,
            'suspended_at' => null,
            'settings' => [],
        ];
    }

    public function trialing(int $days = 14): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn () => ['status' => TenantStatus::PastDue]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    public function withCustomDomain(string $domain): static
    {
        return $this->state(fn () => ['custom_domain' => $domain]);
    }
}
