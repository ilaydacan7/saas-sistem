<?php

declare(strict_types=1);

namespace App\Models;

use App\Tenancy\TenantStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $custom_domain
 * @property TenantStatus $status
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'status',
        'trial_ends_at',
        'paid_until',
        'suspended_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'trial_ends_at' => 'datetime',
            'paid_until' => 'datetime',
            'suspended_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    protected $attributes = [
        'status' => TenantStatus::Trialing->value,
        'settings' => '{}',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function canAccess(): bool
    {
        return $this->status->canAccess();
    }

    public function trialExpired(): bool
    {
        return $this->status === TenantStatus::Trialing
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    public function subscriptionExpired(): bool
    {
        return $this->paid_until !== null && $this->paid_until->isPast();
    }

    public function suspend(): void
    {
        $this->forceFill([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
        ])->save();
    }

    public function reactivate(): void
    {
        $this->forceFill([
            'status' => $this->statusAfterReactivation(),
            'suspended_at' => null,
        ])->save();
    }

    public function extendTrial(int $days): void
    {
        $baslangic = $this->trial_ends_at?->isFuture() ? $this->trial_ends_at : now();

        $this->forceFill([
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => $baslangic->copy()->addDays($days),
        ])->save();
    }

    private function statusAfterReactivation(): TenantStatus
    {
        if ($this->paid_until?->isFuture()) {
            return TenantStatus::Active;
        }

        if ($this->trial_ends_at?->isFuture()) {
            return TenantStatus::Trialing;
        }

        return TenantStatus::PastDue;
    }

    public function host(): string
    {
        if ($this->custom_domain !== null && $this->custom_domain !== '') {
            return $this->custom_domain;
        }

        return $this->slug.'.'.config('tenancy.base_domain');
    }
}
