<?php

namespace App\Tax\Services;

use Carbon\CarbonImmutable;

/**
 * What paying late adds to tax due: a surcharge charged once, and interest
 * for each day after the due date. Micro and small taxpayers pay the
 * reduced rates. Only an estimate: the BIR may add a compromise penalty.
 *
 * @phpstan-type Penalty array{surcharge: float, surcharge_rate: float, interest: float, interest_rate: float, days: int, compromise: float, total: float, due_date: string, pay_on: string}
 */
class Penalties
{
    /**
     * @param  bool  $surcharge  false when only interest runs, as on an amended return whose original was on time
     * @return Penalty
     */
    public function late(float $unpaid, CarbonImmutable $dueDate, CarbonImmutable $payOn, float $yearGross, bool $surcharge = true): array
    {
        $reduced = $yearGross < (float) config('tax.penalties.reduced_below');
        $surchargeRate = $surcharge ? (float) config($reduced ? 'tax.penalties.reduced_surcharge' : 'tax.penalties.surcharge') : 0.0;
        $interestRate = (float) config($reduced ? 'tax.penalties.reduced_interest' : 'tax.penalties.interest');
        $days = max(0, (int) $dueDate->startOfDay()->diffInDays($payOn->startOfDay(), false));
        $surchargeAmount = round($unpaid * $surchargeRate, 2);
        $interest = round($unpaid * $interestRate * $days / 365, 2);

        return [
            'surcharge' => $surchargeAmount,
            'surcharge_rate' => $surchargeRate,
            'interest' => $interest,
            'interest_rate' => $interestRate,
            'days' => $days,
            'compromise' => 0.0,
            'total' => round($surchargeAmount + $interest, 2),
            'due_date' => $dueDate->toDateString(),
            'pay_on' => $payOn->toDateString(),
        ];
    }

    /**
     * Interest for each further week the tax stays unpaid.
     */
    public function weeklyInterest(float $unpaid, float $yearGross): float
    {
        $reduced = $yearGross < (float) config('tax.penalties.reduced_below');

        return round($unpaid * (float) config($reduced ? 'tax.penalties.reduced_interest' : 'tax.penalties.interest') * 7 / 365, 2);
    }
}
