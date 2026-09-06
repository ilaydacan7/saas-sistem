<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Tenant;
use App\Tenancy\Resolvers\DomainResolver;
use App\Tenancy\Resolvers\HeaderResolver;
use App\Tenancy\Resolvers\SubdomainResolver;
use App\Tenancy\Resolvers\TenantResolver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TenantResolverManager
{
    /** @var array<string, class-string<TenantResolver>> */
    private const RESOLVERS = [
        'domain' => DomainResolver::class,
        'subdomain' => SubdomainResolver::class,
        'header' => HeaderResolver::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function isCentralHost(Request $request): bool
    {
        $host = strtolower($request->getHost());

        $central = array_map('strtolower', config('tenancy.central_domains', []));

        if (in_array($host, $central, true)) {
            return true;
        }

        $baseDomain = strtolower((string) config('tenancy.base_domain'));

        if ($baseDomain !== '' && str_ends_with($host, '.'.$baseDomain)) {
            $label = substr($host, 0, -(strlen($baseDomain) + 1));

            return in_array($label, config('tenancy.reserved_subdomains', []), true);
        }

        return false;
    }

    public function resolve(Request $request): ?Tenant
    {
        foreach (config('tenancy.resolvers', []) as $name) {
            if (! isset(self::RESOLVERS[$name])) {
                throw new InvalidArgumentException("Bilinmeyen tenant cozumleyici: [{$name}].");
            }

            /** @var TenantResolver $resolver */
            $resolver = $this->container->make(self::RESOLVERS[$name]);

            if ($tenant = $resolver->resolve($request)) {
                return $tenant;
            }
        }

        return null;
    }
}
