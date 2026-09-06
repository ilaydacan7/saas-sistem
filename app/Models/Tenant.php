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
        'suspended_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'trial_ends_at' => 'datetime',
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

    public function host(): string
    {
        if ($this->custom_domain !== null && $this->custom_domain !== '') {
            return $this->custom_domain;
        }

        return $this->slug.'.'.config('tenancy.base_domain');
    }
}
