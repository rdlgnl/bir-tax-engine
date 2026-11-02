<?php

namespace App\Tax\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Documentary stamp tax on a loan, at the rate in force the day it was
 * signed: ₱1.50 for each ₱200 or fraction until June 30, 2025, then 0.75%
 * of the amount (RA 12214). A term under a year pays its share, by days
 * over 365.
 */
class DocumentaryStampTax
{
    public function __construct(private TaxRules $rules) {}

    public function onLoan(float $amount, CarbonInterface $signedOn, int $termMonths): float
    {
        $rules = $this->rules->on($signedOn);
        $full = $rules->has('dst.loan.percent')
            ? round($amount * $rules->rate('dst.loan.percent'), 2)
            : ceil(round($amount / $rules->rate('dst.loan.per'), 6)) * $rules->rate('dst.loan.rate');

        $signed = CarbonImmutable::parse($signedOn)->startOfDay();
        $days = (int) $signed->diffInDays($signed->addMonthsNoOverflow(max(1, $termMonths)));

        return round($days < 365 ? $full * $days / 365 : $full, 2);
    }

    /**
     * The rate as the form prints it: "P1.50 / P200" or "0.75%".
     */
    public function rateLabel(CarbonInterface $signedOn): string
    {
        $rules = $this->rules->on($signedOn);

        return $rules->has('dst.loan.percent')
            ? rtrim(rtrim(number_format($rules->rate('dst.loan.percent') * 100, 2), '0'), '.').'%'
            : sprintf('P%s / P%d', number_format($rules->rate('dst.loan.rate'), 2), (int) $rules->rate('dst.loan.per'));
    }
}
