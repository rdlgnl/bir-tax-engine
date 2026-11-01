<?php

namespace App\Tax\Services;

use App\Compliance\Models\CompensationCertificate;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;

/**
 * A salary alongside the business, for the annual return. The quarterly
 * returns leave it out; the annual return taxes it with the business and
 * credits what the employer withheld.
 *
 * The year's 2316 gives the exact figures. Until it's in, the salary is the
 * monthly taxable salary for twelve months, with the employer taken to have
 * withheld exactly the tax on it.
 *
 * @phpstan-type SalaryInput array{employer: string|null, employer_tin: string|null, gross: float, non_taxable: float, withheld: float|null, source: 'estimate'|'2316'}
 */
class Compensation
{
    /**
     * @return SalaryInput|null
     */
    public function forYear(TaxProfile $profile, int $year): ?array
    {
        if ($profile->income_source !== IncomeSource::Both) {
            return null;
        }

        $certificate = CompensationCertificate::query()->where('year', $year)->first();

        if ($certificate !== null) {
            return [
                'employer' => $certificate->employer_name,
                'employer_tin' => $certificate->employer_tin,
                'gross' => (float) $certificate->gross_compensation,
                'non_taxable' => (float) $certificate->non_taxable_compensation,
                'withheld' => (float) $certificate->tax_withheld,
                'source' => '2316',
            ];
        }

        return $this->estimate($profile);
    }

    /**
     * The salary from the profile, before the 2316.
     *
     * @return SalaryInput|null
     */
    public function estimate(TaxProfile $profile): ?array
    {
        if ($profile->income_source !== IncomeSource::Both || $profile->monthly_salary === null) {
            return null;
        }

        return [
            'employer' => $profile->employer_name,
            'employer_tin' => null,
            'gross' => round((float) $profile->monthly_salary * 12, 2),
            'non_taxable' => 0.0,
            'withheld' => null,
            'source' => 'estimate',
        ];
    }
}
