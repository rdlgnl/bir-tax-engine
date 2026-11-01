<?php

namespace App\Profiles\Enums;

use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\Regime;

/**
 * The setup wizard, in order. Some steps only apply to some taxpayers:
 * salaried-only people skip the business questions, only those with a job
 * and a business are asked about the job, and only graduated rates ask
 * about expenses.
 */
enum SetupStep: string
{
    case Income = 'income';
    case Work = 'work';
    case Job = 'job';
    case About = 'about';
    case Certificate = 'certificate';
    case Estimate = 'estimate';
    case Method = 'method';
    case Expenses = 'expenses';
    case Withholding = 'withholding';
    case Summary = 'summary';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'How you earn',
            self::Work => 'What you do',
            self::Job => 'Your job',
            self::About => 'About you',
            self::Certificate => 'BIR certificate (COR)',
            self::Estimate => 'Expected income',
            self::Method => 'Tax method',
            self::Expenses => 'Expenses (graduated only)',
            self::Withholding => 'Client withholding',
            self::Summary => 'Your tax profile',
        };
    }

    public function appliesTo(TaxProfile $profile): bool
    {
        if ($profile->income_source === IncomeSource::Employed) {
            return in_array($this, [self::Income, self::About, self::Summary], true);
        }

        return match ($this) {
            self::Job => $profile->income_source === IncomeSource::Both,
            self::Expenses => $profile->regime === Regime::Graduated,
            default => true,
        };
    }

    public function isAnswered(TaxProfile $profile): bool
    {
        return match ($this) {
            self::Income => $profile->income_source !== null,
            self::Work => $profile->work_type !== null,
            self::Job => $profile->monthly_salary !== null,
            self::About => filled($profile->full_name),
            self::Certificate => ! $profile->bir_registered || filled($profile->tin),
            self::Estimate => $profile->expected_income !== null,
            self::Method => $profile->regime !== null,
            self::Expenses => $profile->deduction_method !== null,
            self::Withholding => $profile->clients_withhold !== null,
            self::Summary => true,
        };
    }

    /**
     * @return list<self>
     */
    public static function flow(TaxProfile $profile): array
    {
        return array_values(array_filter(self::cases(), fn (self $step) => $step->appliesTo($profile)));
    }

    /**
     * The step after this one that applies, even when this one doesn't
     * (someone opened a skipped step directly).
     */
    public function next(TaxProfile $profile): self
    {
        $cases = self::cases();
        $after = array_slice($cases, array_search($this, $cases, true) + 1);

        foreach ($after as $step) {
            if ($step->appliesTo($profile)) {
                return $step;
            }
        }

        return self::Summary;
    }

    public function previous(TaxProfile $profile): ?self
    {
        $flow = self::flow($profile);
        $index = array_search($this, $flow, true);

        return $index ? $flow[$index - 1] : null;
    }
}
