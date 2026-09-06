<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantStatus;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Sistem Yöneticisi',
            'email' => 'admin@saas.local',
        ]);

        $acme = Tenant::factory()->create([
            'name' => 'Acme A.Ş.',
            'slug' => 'acme',
            'status' => TenantStatus::Active,
        ]);

        $beta = Tenant::factory()->trialing()->create([
            'name' => 'Beta Yazılım',
            'slug' => 'beta',
        ]);

        foreach ([$acme, $beta] as $tenant) {
            User::factory()->forTenant($tenant)->create([
                'name' => 'Tenant Yöneticisi',
                'email' => 'yonetici@ornek.com',
            ]);
        }
    }
}
