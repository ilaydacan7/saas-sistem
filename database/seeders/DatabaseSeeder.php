<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantRole;
use App\Tenancy\TenantStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->superAdmin()->create([
            'name' => 'Sistem Yöneticisi',
            'email' => 'admin@saas.local',
            'password' => Hash::make('parola12345'),
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

        $gecikmis = Tenant::factory()->pastDue()->create([
            'name' => 'Gecikmiş Ticaret',
            'slug' => 'gecikmis',
            'paid_until' => now()->subWeek(),
        ]);

        foreach ([$acme, $beta, $gecikmis] as $tenant) {
            User::factory()->forTenant($tenant)->owner()->create([
                'name' => 'Şirket Sahibi',
                'email' => 'yonetici@ornek.com',
                'password' => Hash::make('parola12345'),
            ]);

            User::factory()->forTenant($tenant)->role(TenantRole::Member)->create([
                'name' => 'Ekip Üyesi',
                'email' => 'uye@ornek.com',
                'password' => Hash::make('parola12345'),
            ]);
        }
    }
}
