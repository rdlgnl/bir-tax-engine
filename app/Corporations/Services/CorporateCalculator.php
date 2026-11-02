<?php

namespace App\Corporations\Services;

use Carbon\CarbonImmutable;

/**
 * A corporation's 1702Q quarters and 1702-RT year from their inputs alone:
 * no database and no config. CorporateTax gathers the inputs.
 *
 * @phpstan-import-type QuarterTax from CorporateTax
 * @phpstan-import-type AnnualEstimate from CorporateTax
 * @phpstan-import-type Figures from CorporateTax
 * @phpstan-import-type Tax from CorporateTax
 * @phpstan-import-type Ledger from CorporateTax
 * @phpstan-import-type Row from CorporateInputs
 */
class CorporateCalculator
{
    /**
     * Quarters 1 to $upTo of a year, each building on the ones before.
     * Earlier quarters count as paid with what was recorded when they were
     * marked filed, or else with what they came to.
     *
     * @return array<int, QuarterTax>
     */
    public function quarters(CorporateInputs $in, int $upTo = 3): array
    {
        $results = [];
        $toDate = $this->figures(null, $in);
        $grossByQuarter = [];
        $paidRegular = $paidMcit = $withheldBefore = 0.0;

        for ($q = 1; $q <= $upTo; $q++) {
            $row = $in->rows[$q] ?? null;
            $thisQuarter = $this->figures($row, $in);
            $previousTaxable = $toDate['taxable_income'];
            $toDate = $this->add($toDate, $thisQuarter);
            $grossByQuarter[] = $thisQuarter['gross_income'];
            $tax = $this->tax($in, $toDate['taxable_income'], $toDate['gross_income'], $q * 3);
            $withheld = $row['withheld'] ?? 0.0;
            $credits = round($paidRegular + $paidMcit + $withheldBefore + $withheld, 2);
            $dates = $in->dates[$q];

            $results[$q] = [
                'year' => $in->year,
                'quarter' => $q,
                'has_figures' => $row !== null,
                'this_quarter' => $thisQuarter,
                'to_date' => $toDate,
                'previous_taxable_income' => $previousTaxable,
                'gross_by_quarter' => $grossByQuarter,
                ...$tax,
                'paid_regular' => $paidRegular,
                'paid_mcit' => $paidMcit,
                'withheld_previous' => $withheldBefore,
                'withheld_this_quarter' => $withheld,
                'credits' => $credits,
                'payable' => round($tax['tax_due'] - $credits, 2),
                'due_date' => $dates['due']->toDateString(),
                'statutory_date' => $dates['statutory']->toDateString(),
                'due_note' => $dates['due_note'],
                'status' => $this->status($in, $row, $dates['period_end'], $dates['due']),
                'filed_on' => $row['filed_on'] ?? null,
                'reference_number' => $row['reference_number'] ?? null,
                'amount_paid' => $row['amount_paid'] ?? null,
            ];

            $paid = $results[$q]['amount_paid'] ?? max(0.0, $results[$q]['payable']);
            $tax['uses'] === 'mcit' ? $paidMcit += $paid : $paidRegular += $paid;
            $withheldBefore += $withheld;
        }

        return $results;
    }

    /**
     * One year's 1702-RT on what earlier years carried into it, and what it
     * carries on. Until the 4th quarter is in the books, the year is
     * estimated from the quarters so far, as if the rest go the same.
     *
     * @param  Ledger  $ledger
     * @return array{annual: AnnualEstimate, ledger: Ledger}
     */
    public function year(CorporateInputs $in, array $ledger): array
    {
        $year = $in->year;
        $rows = $in->rows;
        $estimated = ! isset($rows[4]);
        $factor = $estimated ? 4 / count($rows) : 1.0;
        $figures = $this->figures(null, $in);

        foreach ($rows as $row) {
            $figures = $this->add($figures, $this->figures($row, $in));
        }

        $figures = array_map(fn (float $amount) => round($amount * $factor, 2), $figures);
        $withheld = round(array_sum(array_column($rows, 'withheld')) * $factor, 2);
        $withheldBefore = round(array_sum(array_column(array_filter($rows, fn (int $q) => $q < 4, ARRAY_FILTER_USE_KEY), 'withheld')), 2);
        // What's still usable this year; the rest has run out.
        $ledger = array_values(array_filter($ledger, fn (array $entry) => $entry['last_year'] >= $year && $entry['remaining'] > 0));
        $carried = [];

        // Losses first in, first used, against a profit; not under the OSD,
        // which replaces the itemized deductions NOLCO is one of (NIRC Sec.
        // 34(L); 1702-RT items 34-38). The years run on: "three consecutive
        // taxable years" (Sec. 34(D)(3)). REVIEW: that reading.
        $room = $in->deductionMethod === 'itemized' ? max(0.0, $figures['taxable_income']) : 0.0;

        foreach ($ledger as $i => $entry) {
            if ($entry['kind'] === 'nolco') {
                $applied = round(min($room, $entry['remaining']), 2);
                $room -= $applied;
                $ledger[$i]['remaining'] = round($entry['remaining'] - $applied, 2);
                $carried[] = ['kind' => 'nolco', 'year' => $entry['year'], 'available' => $entry['remaining'], 'applied' => $applied, 'last_year' => $entry['last_year']];
            }
        }

        $nolco = round(array_sum(array_map(fn (array $c) => $c['applied'], $carried)), 2);
        $net = round($figures['taxable_income'] - $nolco, 2);
        $tax = $this->tax($in, $net, $figures['gross_income'], 12);

        // Excess MCIT is credited against the regular tax, in a year it's the
        // one due (NIRC Sec. 27(E)(1); 1702Q item 15). Like the other credits,
        // it comes off the tax due with no floor at the year's MCIT (1702-RT
        // items 43 and 47).
        $room = $tax['uses'] === 'regular' ? $tax['tax_due'] : 0.0;

        foreach ($ledger as $i => $entry) {
            if ($entry['kind'] === 'excess_mcit') {
                $applied = round(min($room, $entry['remaining']), 2);
                $room -= $applied;
                $ledger[$i]['remaining'] = round($entry['remaining'] - $applied, 2);
                $carried[] = ['kind' => 'excess_mcit', 'year' => $entry['year'], 'available' => $entry['remaining'], 'applied' => $applied, 'last_year' => $entry['last_year']];
            }
        }

        $excessApplied = round(array_sum(array_map(fn (array $c) => $c['kind'] === 'excess_mcit' ? $c['applied'] : 0.0, $carried)), 2);

        if ($figures['taxable_income'] < 0) {
            // 2020 and 2021 losses carry five years (RR 25-2020).
            $years = in_array($year, [2020, 2021], true) ? 5 : 3;
            $ledger[] = ['kind' => 'nolco', 'year' => $year, 'remaining' => round(-$figures['taxable_income'], 2), 'last_year' => $year + $years];
        }

        if ($tax['uses'] === 'mcit') {
            $ledger[] = ['kind' => 'excess_mcit', 'year' => $year, 'remaining' => round($tax['mcit'] - $tax['regular_tax'], 2), 'last_year' => $year + 3];
        }

        $paidRegular = $paidMcit = 0.0;

        foreach ($this->quarters($in) as $quarter) {
            if ($quarter['has_figures']) {
                $paid = $quarter['amount_paid'] ?? max(0.0, $quarter['payable']);
                $quarter['uses'] === 'mcit' ? $paidMcit += $paid : $paidRegular += $paid;
            }
        }

        $payments = round($paidRegular + $paidMcit, 2);
        $credits = round($payments + $withheld + $excessApplied, 2);
        $fourth = $rows[4] ?? null;
        $dates = $in->dates[4];

        $annual = [
            'year' => $year,
            'estimated' => $estimated,
            'quarters_entered' => count($rows),
            'figures' => $figures,
            'withheld' => $withheld,
            'withheld_previous' => $withheldBefore,
            'withheld_fourth_quarter' => round($withheld - $withheldBefore, 2),
            ...$tax,
            'paid_regular' => round($paidRegular, 2),
            'paid_mcit' => round($paidMcit, 2),
            'quarterly_payments' => $payments,
            'credits' => $credits,
            'payable' => round($tax['tax_due'] - $credits, 2),
            'due_date' => $dates['due']->toDateString(),
            'status' => $this->status($in, $fourth, $dates['period_end'], $dates['due']),
            'filed_on' => $fourth['filed_on'] ?? null,
            'reference_number' => $fourth['reference_number'] ?? null,
            'amount_paid' => $fourth['amount_paid'] ?? null,
            'nolco' => $nolco,
            'net_taxable_income' => $net,
            'excess_mcit_applied' => $excessApplied,
            'carried' => $carried,
        ];

        return ['annual' => $annual, 'ledger' => $ledger];
    }

    /**
     * @param  Row|null  $row
     * @return 'open'|'due'|'overdue'|'filed'
     */
    private function status(CorporateInputs $in, ?array $row, CarbonImmutable $periodEnd, CarbonImmutable $due): string
    {
        return match (true) {
            ($row['filed_on'] ?? null) !== null => 'filed',
            $in->today->lessThanOrEqualTo($periodEnd->endOfDay()) => 'open',
            $in->today->greaterThan($due->endOfDay()) => 'overdue',
            default => 'due',
        };
    }

    /**
     * The regular tax on the year's rates, and the MCIT on the gross income
     * of the months so far at their average rate: in 2023, 1% for January to
     * June and 2% after, so 1.5% for the year (RMC 69-2023).
     *
     * @return Tax
     */
    private function tax(CorporateInputs $in, float $taxableIncome, float $grossIncome, int $months): array
    {
        $rules = $in->rules;
        $small = $taxableIncome <= $rules['small_taxable_income'] && $in->totalAssets <= $rules['small_total_assets'];
        $rate = $small ? $rules['small_rate'] : $rules['rate'];
        $regular = round(max(0.0, $taxableIncome) * $rate, 2);
        $mcitRate = array_sum(array_map(fn (int $month) => $rules['mcit_rates'][$month], range(1, $months))) / $months;
        $mcit = $in->mcitApplies ? round(max(0.0, $grossIncome) * $mcitRate, 2) : 0.0;

        return [
            'rate' => $rate,
            'small' => $small,
            'regular_tax' => $regular,
            'mcit_applies' => $in->mcitApplies,
            'mcit' => $mcit,
            'uses' => $mcit > $regular ? 'mcit' : 'regular',
            'tax_due' => max($regular, $mcit),
        ];
    }

    /**
     * One quarter's figures, with the 40% optional standard deduction in
     * place of the books' deductions when the corporation chose it.
     *
     * @param  Row|null  $row
     * @return Figures
     */
    private function figures(?array $row, CorporateInputs $in): array
    {
        $sales = $row['sales'] ?? 0.0;
        $cost = $row['cost_of_sales'] ?? 0.0;
        $other = $row['other_income'] ?? 0.0;
        $grossIncome = round($sales - $cost + $other, 2);
        $deductions = $in->deductionMethod === 'osd'
            ? round(max(0.0, $grossIncome) * $in->rules['osd_rate'], 2)
            : ($row['deductions'] ?? 0.0);

        return [
            'sales' => $sales,
            'cost_of_sales' => $cost,
            'gross_from_operation' => round($sales - $cost, 2),
            'other_income' => $other,
            'gross_income' => $grossIncome,
            'deductions' => $deductions,
            'taxable_income' => round($grossIncome - $deductions, 2),
        ];
    }

    /**
     * @param  Figures  $a
     * @param  Figures  $b
     * @return Figures
     */
    private function add(array $a, array $b): array
    {
        foreach ($a as $key => $amount) {
            $a[$key] = round($amount + $b[$key], 2);
        }

        return $a;
    }
}
