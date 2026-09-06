<?php

declare(strict_types=1);

namespace App\Tenancy\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TenantNotFoundException extends NotFoundHttpException
{
    public static function forHost(string $host): self
    {
        return new self(sprintf('"%s" host adresi icin kayitli bir tenant bulunamadi.', $host));
    }
}
