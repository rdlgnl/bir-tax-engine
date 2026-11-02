<?php

namespace App\Returns\Services;

use App\Compliance\Rules\NotInClosedYear;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Services\Penalties;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\TaxRules;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Number;

/**
 * Filed quarterly returns whose quarter changed afterwards: the figures
 * last filed against the figures now, and what amending the return costs.
 * Only the 1701Q is amended here; a year whose annual return is filed is
 * closed to changes.
 *
 * The amended return keeps the original's due date: interest runs on the
 * difference from then, and a surcharge only if the original was late.
 *
 * The quarter is recomputed on the rules it was filed under, kept with the
 * filed return, so only changed records call for an amendment, not a newer
 * rule pack.
 *
 * @phpstan-import-type QuarterResult from TaxCalculationService
 * @phpstan-import-type Penalty from Penalties
 * @phpstan-import-type Snapshot from ReturnForm
 *
 * @phpstan-type Figures array{gross: float, reduction: float, income_tax: float, withheld: float, due: float}
 * @phpstan-type Amendment array{year: int, quarter: int, form: string, number: int, filed_on: string, due_date: string, on_time: bool, reason: string, filed: Figures, amended: Figures, paid: float, extra: float, overpaid: float, penalties: Penalty|null, rule_set: array{date: string, values: array<string, mixed>}|null}
 */
class Amendments
{
    private const FORM = '1701Q';

    public function __construct(
        private TaxCalculationService $engine,
        private ReturnForm $returns,
        private Penalties $penalties,
        private TaxRules $rules,
    ) {}

    /**
     * Every filed quarter that needs amending, oldest first.
     *
     * @return list<Amendment>
     */
    public function needed(TaxProfile $profile, ?CarbonImmutable $today = null): array
    {
        $found = [];
        $filed = Filing::query()->where('form', self::FORM)->where('amendment', 0)->orderBy('year')->orderBy('quarter')->get();

        foreach ($filed as $filing) {
            if (($amendment = $this->for($profile, $filing->year, $filing->quarter, $today)) !== null) {
                $found[] = $amendment;
            }
        }

        return $found;
    }

    /**
     * The amendment a filed quarter needs, or null when it still matches.
     *
     * @return Amendment|null
     */
    public function for(TaxProfile $profile, int $year, int $quarter, ?CarbonImmutable $today = null): ?array
    {
        $today ??= CarbonImmutable::now();
        $filings = Filing::query()->where('year', $year)->where('quarter', $quarter)->where('form', self::FORM)->orderBy('amendment')->get();
        $original = $filings->first();
        $latest = $filings->last();

        if ($original === null || $latest === null || NotInClosedYear::closes(CarbonImmutable::create($year))) {
            return null;
        }

        $filedSnapshot = $this->returns->snapshotOf($latest, $profile);
        $ruleSet = $filedSnapshot['rule_set'] ?? null;
        $result = $this->rules->pinned($year, $ruleSet, fn () => $this->engine->forYear($profile, $year, $today));
        $now = $result['quarters'][$quarter];
        $before = $filedSnapshot['figures'];
        $filed = $this->figures($before);
        $amended = $this->figures($now);

        if (abs($amended['income_tax'] - $filed['income_tax']) < 0.5 && abs($amended['withheld'] - $filed['withheld']) < 0.5 && abs($amended['gross'] - $filed['gross']) < 0.5) {
            return null;
        }

        $originalFigures = $this->returns->snapshotOf($original, $profile)['figures'];
        $dueDate = $originalFigures['due_date'];
        $onTime = $original->filed_on->toDateString() <= $dueDate;
        $paid = round((float) $filings->sum(fn (Filing $f) => $f->amount_paid !== null ? $f->taxPaid() : 0.0), 2);
        $extra = round(max(0.0, $amended['due'] - $paid), 2);

        return [
            'year' => $year,
            'quarter' => $quarter,
            'form' => self::FORM,
            'number' => (int) $latest->amendment + 1,
            'filed_on' => $original->filed_on->toDateString(),
            'due_date' => $dueDate,
            'on_time' => $onTime,
            'reason' => $this->reason($year, $quarter, $latest),
            'filed' => $filed,
            'amended' => $amended,
            'paid' => $paid,
            'extra' => $extra,
            'overpaid' => round(max(0.0, $paid - $amended['due']), 2),
            'penalties' => $extra > 0
                ? $this->penalties->late($extra, CarbonImmutable::parse($dueDate), $today, $result['gross'], surcharge: ! $onTime)
                : null,
            'rule_set' => $ruleSet,
        ];
    }

    /**
     * @param  Amendment  $amendment
     * @return Snapshot
     */
    public function snapshot(TaxProfile $profile, array $amendment, ?CarbonImmutable $today = null): array
    {
        return $this->rules->pinned($amendment['year'], $amendment['rule_set'], fn () => $this->returns->amendedSnapshot($profile, $amendment['year'], $amendment['quarter'], $amendment['form'], [
            'number' => $amendment['number'],
            'previously_paid' => $amendment['paid'],
            'original_filed_on' => $amendment['filed_on'],
            'on_time' => $amendment['on_time'],
        ], $amendment['penalties'], $today));
    }

    /**
     * @param  QuarterResult  $figures
     * @return Figures
     */
    private function figures(array $figures): array
    {
        return [
            'gross' => $figures['gross'],
            'reduction' => $figures['exempt_amount'] + $figures['deduction'],
            'income_tax' => $figures['income_tax'],
            'withheld' => $figures['withheld'],
            'due' => $figures['due'],
        ];
    }

    /**
     * "You added a ₱20,000 payment from Jun 28 after Q2 was filed."
     */
    private function reason(int $year, int $quarter, Filing $latest): string
    {
        $changed = Transaction::query()->forQuarter($year, $quarter)
            ->where(fn ($q) => $q->where('created_at', '>', $latest->created_at)->orWhere('updated_at', '>', $latest->created_at))
            ->orderBy('occurred_on')
            ->get();
        $first = $changed->first();

        if ($first === null) {
            return "Your Q{$quarter} records changed after it was filed.";
        }

        $verb = $first->created_at > $latest->created_at ? 'added' : 'changed';
        $what = $first->type->value === 'expense' ? 'expense' : 'payment';
        $more = $changed->count() > 1 ? sprintf(' and %d other %s', $changed->count() - 1, $changed->count() === 2 ? 'change' : 'changes') : '';

        return sprintf('You %s a ₱%s %s from %s%s after Q%d was filed.', $verb, Number::format((float) $first->amount), $what, $first->occurred_on->format('M j'), $more, $quarter);
    }
}
