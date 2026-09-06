<?php

declare(strict_types=1);

namespace App\Modules;

enum ProductType: string
{
    case Product = 'product';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Ürün',
            self::Service => 'Hizmet',
        };
    }

    public function tracksStock(): bool
    {
        return $this === self::Product;
    }
}
