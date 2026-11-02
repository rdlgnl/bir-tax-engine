<?php

namespace App\Tax\Enums;

use App\Profiles\Models\TaxYear;

/**
 * The size class the BIR assigns each taxpayer under the Ease of Paying
 * Taxes Act (RA 11976; RR 8-2024), by gross sales: micro, small, medium,
 * large. Micro and small pay reduced penalties.
 */
enum TaxpayerClassification: string
{
    case Micro = 'micro';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';

    /**
     * The class a year's gross sales point to. Only an estimate: the BIR
     * assigns the class, and it shows in ORUS.
     */
    public static function fromGross(float $gross): self
    {
        return self::fromGrossUnder($gross, [
            'micro' => (float) config('tax.classification.micro_below'),
            'small' => (float) config('tax.classification.small_below'),
            'medium' => (float) config('tax.classification.medium_below'),
        ]);
    }

    /**
     * The same, against the given limits: each class is below its limit.
     *
     * @param  array{micro: float, small: float, medium: float}  $below
     */
    public static function fromGrossUnder(float $gross, array $below): self
    {
        return match (true) {
            $gross < $below['micro'] => self::Micro,
            $gross < $below['small'] => self::Small,
            $gross < $below['medium'] => self::Medium,
            default => self::Large,
        };
    }

    /**
     * A year's class: the one the BIR assigned, set on the profile, else
     * the estimate from the year's gross sales.
     */
    public static function forYear(int $year, float $gross): self
    {
        return TaxYear::query()->where('year', $year)->first()->classification ?? self::fromGross($gross);
    }

    /**
     * Micro and small: reduced penalties, and the 1701-MS (RR 8-2024).
     */
    public function microOrSmall(): bool
    {
        return $this === self::Micro || $this === self::Small;
    }

    public function label(): string
    {
        return match ($this) {
            self::Micro => 'Micro (sales under ₱3M)',
            self::Small => 'Small (₱3M to under ₱20M)',
            self::Medium => 'Medium (₱20M to under ₱1B)',
            self::Large => 'Large (₱1B and up)',
        };
    }

    public function reducedPenalties(): bool
    {
        return $this->microOrSmall();
    }
}
