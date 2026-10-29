<?php

namespace App\Profiles\Enums;

enum IncomeSource: string
{
    case Employed = 'employed';
    case Business = 'business';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Employed => 'employed',
            self::Business => 'business',
            self::Both => 'job + business',
        };
    }
}
