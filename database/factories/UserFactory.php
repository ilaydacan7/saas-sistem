<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'role' => TenantRole::Member,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn () => ['tenant_id' => $tenant->getKey()]);
    }

    public function role(TenantRole $rol): static
    {
        return $this->state(fn () => ['role' => $rol]);
    }

    public function owner(): static
    {
        return $this->role(TenantRole::Owner);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['tenant_id' => null, 'is_super_admin' => true]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
