<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Tenancy\Exceptions\TenantContextMissingException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->isWithoutTenant()) {
            return;
        }

        if (! $context->has()) {
            if (config('tenancy.strict')) {
                throw TenantContextMissingException::forModel($model::class);
            }

            return;
        }

        $builder->where(
            $model->qualifyColumn($model->getTenantColumn()),
            $context->id()
        );
    }
}
