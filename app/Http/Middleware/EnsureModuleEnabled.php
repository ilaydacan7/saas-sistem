<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Module;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureModuleEnabled
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $hedef = Module::tryFrom($module);

        if ($hedef === null || ! $this->context->getOrFail()->hasModule($hedef)) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
