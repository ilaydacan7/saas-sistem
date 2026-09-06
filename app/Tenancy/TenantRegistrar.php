<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantRegistrar
{
    /**
     * @param  array{company: string, slug: string, name: string, email: string, password: string}  $data
     * @return array{0: Tenant, 1: User}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $trialDays = config('billing.trial_days');

            $tenant = Tenant::create([
                'name' => $data['company'],
                'slug' => $data['slug'],
                'status' => TenantStatus::Trialing,
                'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays) : null,
            ]);

            $owner = User::make([
                'tenant_id' => $tenant->getKey(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => TenantRole::Owner,
            ]);

            $owner->email_verified_at = now();
            $owner->save();

            return [$tenant, $owner];
        });
    }
}
