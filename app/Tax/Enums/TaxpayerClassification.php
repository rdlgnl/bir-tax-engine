<?php

namespace App\Tax\Enums;

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
        return match (true) {
            $gross < (float) config('tax.classification.micro_below') => self::Micro,
            $gross < (float) config('tax.classification.small_below') => self::Small,
            $gross < (float) config('tax.classification.medium_below') => self::Medium,
            default => self::Large,
        };
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
        return $this === self::Micro || $this === self::Small;
    }
}
