<?php

namespace App\Tax\Services;

use App\Tax\Enums\TaxpayerClassification;
use Carbon\CarbonImmutable;

/**
 * RR 4-2026's one-time abatement for micro taxpayers: the tax and penalties
 * of a taxable year, up to ₱80,000, delinquent or under an open case as of
 * December 31, 2025, settled for a ₱5,000 fee on Form 0605. It's applied
 * for until December 31, 2026 (RMC 84-2026).
 *
 * @phpstan-type Offer array{until: string, fee: float, ceiling: float, source: string}
 */
class Abatement
{
    /**
     * The offer for a return still unfiled, if it qualifies: due by the
     * cut-off, from a micro taxpayer's year, while the window is open.
     *
     * @return Offer|null
     */
    public function offer(int $year, CarbonImmutable $dueDate, float $yearGross, ?CarbonImmutable $today = null): ?array
    {
        $today ??= CarbonImmutable::now();
        $until = CarbonImmutable::parse((string) config('tax.abatement.until'))->endOfDay();

        if ($today->greaterThan($until)
            || $dueDate->greaterThan(CarbonImmutable::parse((string) config('tax.abatement.cases_as_of')))
            || TaxpayerClassification::forYear($year, $yearGross) !== TaxpayerClassification::Micro) {
            return null;
        }

        return [
            'until' => $until->toDateString(),
            'fee' => (float) config('tax.abatement.fee'),
            'ceiling' => (float) config('tax.abatement.ceiling'),
            'source' => (string) config('tax.abatement.source'),
        ];
    }
}
