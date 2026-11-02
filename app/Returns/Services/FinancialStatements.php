<?php

namespace App\Returns\Services;

use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;

/**
 * What goes with an individual's annual return: nothing on 8% or the 40%
 * deduction; with itemized deductions, financial statements (or an Account
 * Information Form), audited by an independent CPA once gross sales exceed
 * ₱3,000,000 (RMC 17-2019; NIRC Sec. 232(A); RR 8-2018 for a year that
 * left 8%).
 */
class FinancialStatements
{
    /**
     * @return 'none'|'unaudited'|'audited'
     */
    public static function required(?string $regime, ?string $deductionMethod, float $gross): string
    {
        return match (true) {
            $regime !== Regime::Graduated->value || $deductionMethod !== DeductionMethod::Itemized->value => 'none',
            $gross > (float) config('tax.financial_statements.audit_above') => 'audited',
            default => 'unaudited',
        };
    }
}
