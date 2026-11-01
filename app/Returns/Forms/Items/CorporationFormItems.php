<?php

namespace App\Returns\Forms\Items;

use App\Corporations\Models\Corporation;
use Illuminate\Support\Str;

/**
 * A corporation's returns (1702Q, 1702-RT) are filled from its own record
 * and its books, not from the taxpayer's profile.
 */
abstract class CorporationFormItems
{
    /**
     * @return array{string, string, string}
     */
    protected function tin(Corporation $corporation): array
    {
        $digits = substr(preg_replace('/\D/', '', (string) $corporation->tin), 0, 9);

        return [substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 3)];
    }

    /**
     * Text over two rows of boxes, broken between words.
     *
     * @return array{string, string}
     */
    protected function wrap(?string $text, int $width): array
    {
        $text ??= '';

        if (mb_strlen($text) <= $width) {
            return [$text, ''];
        }

        $first = Str::of(mb_substr($text, 0, $width + 1))->beforeLast(' ')->trim()->value() ?: mb_substr($text, 0, $width);

        return [$first, trim(mb_substr($text, mb_strlen($first)))];
    }

    protected function upper(?string $value): ?string
    {
        return $value === null ? null : Str::upper(trim($value));
    }

    /**
     * Whole pesos: 49 centavos or less drop, 50 or more round up.
     */
    protected function peso(float $amount): int
    {
        return (int) round($amount, 0, PHP_ROUND_HALF_UP);
    }
}
