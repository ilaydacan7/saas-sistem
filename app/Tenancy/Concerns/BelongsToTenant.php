<?php

declare(strict_types=1);

namespace App\Tenancy\Concerns;

use App\Models\Tenant;
use App\Tenancy\Exceptions\TenantContextMissingException;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            $context = app(TenantContext::class);
            $column = $model->getTenantColumn();

            if ($model->getAttribute($column) !== null) {
                return;
            }

            if (! $context->has()) {
                throw TenantContextMissingException::forModel($model::class);
            }

            $model->setAttribute($column, $context->id());
        });
    }

    public function getTenantColumn(): string
    {
        return property_exists($this, 'tenantColumn') ? $this->tenantColumn : 'tenant_id';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, $this->getTenantColumn());
    }

    public function scopeAcrossTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    public function scopeForTenant(Builder $query, Tenant|int $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;

        return $query
            ->withoutGlobalScope(TenantScope::class)
            ->where($this->qualifyColumn($this->getTenantColumn()), $tenantId);
    }
}
