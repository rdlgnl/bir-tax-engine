<?php

namespace App\Tax\Enums;

enum Regime: string
{
    case EightPercent = 'eight_percent';
    case Graduated = 'graduated';

    public function label(): string
    {
        return match ($this) {
            self::EightPercent => 'Flat 8%',
            self::Graduated => 'Graduated rates',
        };
    }
}
