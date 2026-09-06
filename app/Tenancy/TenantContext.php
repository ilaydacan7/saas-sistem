<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Tenant;
use Closure;

class TenantContext
{
    private ?Tenant $tenant = null;

    private int $withoutTenantDepth = 0;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    public function getOrFail(): Tenant
    {
        return $this->tenant ?? throw Exceptions\TenantContextMissingException::forModel(Tenant::class);
    }

    public function isWithoutTenant(): bool
    {
        return $this->withoutTenantDepth > 0;
    }

    /**
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @return TReturn
     */
    public function runFor(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
        }
    }

    /**
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @return TReturn
     */
    public function runWithoutTenant(Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = null;
        $this->withoutTenantDepth++;

        try {
            return $callback();
        } finally {
            $this->withoutTenantDepth--;
            $this->tenant = $previous;
        }
    }
}
