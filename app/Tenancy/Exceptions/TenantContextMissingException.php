<?php

declare(strict_types=1);

namespace App\Tenancy\Exceptions;

use RuntimeException;

final class TenantContextMissingException extends RuntimeException
{
    public static function forModel(string $model): self
    {
        return new self(sprintf(
            '[%s] modeli tenant baglami olmadan sorgulandi. Bilincli bir islem ise '.
            'TenantContext::runWithoutTenant() icinde calistirin.',
            $model
        ));
    }
}
