<?php

namespace App\Transactions\Enums;

enum ExpenseCategory: string
{
    case Internet = 'internet';
    case Rent = 'rent';
    case Equipment = 'equipment';
    case Software = 'software';
    case Transport = 'transport';
    case Person = 'person';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Internet => 'internet & phone',
            self::Rent => 'rent',
            self::Equipment => 'equipment',
            self::Software => 'software',
            self::Transport => 'transport',
            self::Person => 'paid a person',
            self::Other => 'other',
        };
    }
}
