<?php

namespace App\Loans\Services;

use App\Loans\Models\Loan;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\DocumentaryStampTax;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\Penalties;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;

/**
 * Where a loan's documentary stamp tax stands: what it is, when Form 2000
 * is due, and whether it's filed, late, or the bank's to file.
 *
 * @phpstan-import-type Penalty from Penalties
 *
 * @phpstan-type LoanStatus 'bank'|'due'|'overdue'|'filed'
 * @phpstan-type LoanDst array{tax: float, due_date: string, status: LoanStatus, penalties: Penalty|null, total: float}
 */
class LoanTax
{
    public function __construct(
        private DocumentaryStampTax $dst,
        private FilingCalendar $calendar,
        private Penalties $penalties,
        private TaxCalculationService $engine,
    ) {}

    /**
     * @return LoanDst
     */
    public function for(Loan $loan, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now();
        $tax = $this->dst->onLoan((float) $loan->amount, $loan->signed_on, $loan->term_months);
        $due = $this->calendar->dstDue($loan->signed_on);
        $status = match (true) {
            $loan->filedByLender() => 'bank',
            $loan->filed_on !== null => 'filed',
            $today->greaterThan($due->endOfDay()) => 'overdue',
            default => 'due',
        };
        $penalties = $status === 'overdue'
            ? $this->penalties->late($tax, $due, $today->startOfDay(), $this->engine->forYear(TaxProfile::current(), $loan->signed_on->year, $today)['gross'])
            : null;

        return [
            'tax' => $tax,
            'due_date' => $due->toDateString(),
            'status' => $status,
            'penalties' => $penalties,
            'total' => round($tax + ($penalties['total'] ?? 0.0), 2),
        ];
    }
}
