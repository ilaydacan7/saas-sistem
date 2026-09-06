<?php

declare(strict_types=1);

namespace App\Modules;

enum CustomerType: string
{
    case Individual = 'individual';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Bireysel',
            self::Company => 'Kurumsal',
        };
    }
}
