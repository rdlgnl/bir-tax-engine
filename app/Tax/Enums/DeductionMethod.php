<?php

namespace App\Tax\Enums;

enum DeductionMethod: string
{
    case Osd = 'osd';
    case Itemized = 'itemized';

    public function label(): string
    {
        return match ($this) {
            self::Osd => 'Flat 40% deduction',
            self::Itemized => 'Actual expenses',
        };
    }
}
