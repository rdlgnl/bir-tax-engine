<?php

namespace App\Tax\Calculation;

use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Enums\TaxpayerClassification;

/**
 * Who the taxpayer was for a year's tax: the method and registrations the
 * calculation needs, as plain values.
 */
final readonly class YearTaxpayer
{
    public function __construct(
        public ?Regime $regime,
        public ?DeductionMethod $deductionMethod,
        public IncomeSource $incomeSource,
        public bool $vatRegistered,
    ) {}

    /**
     * From a profile as it stood for the year (TaxProfile::forYear).
     */
    public static function of(TaxProfile $profile): self
    {
        return new self($profile->regime, $profile->deduction_method, $profile->income_source, (bool) $profile->vat_registered);
    }

    /**
     * Once an 8% year's sales pass ₱3,000,000: graduated rates, with
     * expenses itemized unless a deduction method was chosen (RR 8-2018).
     */
    public function onGraduatedRates(): self
    {
        return new self(Regime::Graduated, $this->deductionMethod ?? DeductionMethod::Itemized, $this->incomeSource, $this->vatRegistered);
    }

    public function purelySelfEmployed(): bool
    {
        return $this->incomeSource !== IncomeSource::Both;
    }

    public function owesPercentageTax(): bool
    {
        return $this->regime === Regime::Graduated && ! $this->vatRegistered;
    }

    /**
     * The income tax return for a quarter. The annual return of the purely
     * self-employed is the 1701-MS for micro and small taxpayers (1701-MS
     * guidelines, August 2024), else the 1701A on 8% or OSD; anyone else's
     * is the 1701.
     */
    public function incomeTaxForm(int $quarter, TaxpayerClassification $class = TaxpayerClassification::Micro): string
    {
        return match (true) {
            $quarter < 4 => '1701Q',
            $this->purelySelfEmployed() && $class->microOrSmall() => '1701-MS',
            $this->purelySelfEmployed() && ($this->regime === Regime::EightPercent || $this->deductionMethod === DeductionMethod::Osd) => '1701A',
            default => '1701',
        };
    }
}
