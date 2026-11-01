<?php

namespace App\Tax\Services;

/**
 * Documentary stamp tax on a loan: a rate for each ₱200 (or fraction) of
 * the amount, and only a share of it when the term is under a year.
 */
class DocumentaryStampTax
{
    public function onLoan(float $amount, int $termMonths): float
    {
        $per = (float) config('tax.dst.loan.per');
        $full = ceil(round($amount / $per, 6)) * (float) config('tax.dst.loan.rate');

        return round($termMonths < 12 ? $full * max(1, $termMonths) / 12 : $full, 2);
    }
}
