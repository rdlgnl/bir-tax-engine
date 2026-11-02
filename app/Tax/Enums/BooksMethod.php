<?php

namespace App\Tax\Enums;

/**
 * When a sale counts as income for income tax: the method the books are
 * kept on (NIRC Secs. 43–45). Percentage tax and VAT don't follow it; from
 * April 27, 2024 they count a sale on its invoice date (RR 3-2024).
 */
enum BooksMethod: string
{
    /** On the invoice date, paid or not. */
    case Accrual = 'accrual';

    /** When the money comes in. */
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Accrual => 'When invoiced (accrual)',
            self::Cash => 'When paid (cash)',
        };
    }
}
