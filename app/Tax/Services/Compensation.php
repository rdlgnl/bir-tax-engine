<?php

namespace App\Tax\Services;

use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;

/**
 * A salary alongside the business, for the annual return. The quarterly
 * returns leave it out; the annual return taxes it with the business and
 * credits what the employer withheld.
 *
 * Until the 2316 is in, it's the monthly taxable salary for twelve months,
 * with the employer taken to have withheld exactly the tax on it.
 *
 * @phpstan-type SalaryInput array{employer: string|null, gross: float, non_taxable: float, withheld: float|null, source: 'estimate'|'2316'}
 */
class Compensation
{
    /**
     * @return SalaryInput|null
     */
    public function forYear(TaxProfile $profile, int $year): ?array
    {
        if ($profile->income_source !== IncomeSource::Both || $profile->monthly_salary === null) {
            return null;
        }

        return [
            'employer' => $profile->employer_name,
            'gross' => round((float) $profile->monthly_salary * 12, 2),
            'non_taxable' => 0.0,
            'withheld' => null,
            'source' => 'estimate',
        ];
    }
}
