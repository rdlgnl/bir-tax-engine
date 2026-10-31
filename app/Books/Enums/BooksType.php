<?php

namespace App\Books\Enums;

/**
 * How the taxpayer's books are registered with the BIR.
 */
enum BooksType: string
{
    case Manual = 'manual';
    case LooseLeaf = 'loose_leaf';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual (stamped)',
            self::LooseLeaf => 'Loose-leaf',
        };
    }
}
