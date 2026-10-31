<?php

namespace App\Transactions\Enums;

/**
 * How VAT applies to a sale. Purchases only carry the VAT on their invoice.
 */
enum VatKind: string
{
    case Vatable = 'vatable';
    case ZeroRated = 'zero_rated';
    case Exempt = 'exempt';

    public function label(): string
    {
        return match ($this) {
            self::Vatable => 'Vatable (12%)',
            self::ZeroRated => 'Zero-rated',
            self::Exempt => 'VAT-exempt',
        };
    }
}
