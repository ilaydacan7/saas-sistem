<?php

declare(strict_types=1);

namespace App\Modules;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Nakit',
            self::Card => 'Kart',
            self::Transfer => 'Havale / EFT',
        };
    }
}
