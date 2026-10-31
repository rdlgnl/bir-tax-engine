<?php

namespace App\Transactions\Services;

use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finding records: by words or an amount, by kind, by period, and by the
 * quick filters that point at something to fix.
 *
 * @phpstan-type Filters array{search?: string|null, type?: string|null, quarter?: int|string|null, flag?: string|null}
 */
class TransactionFilter
{
    /** Quick filters, each a question about a record. */
    public const FLAGS = ['missing_2307', 'no_invoice', 'after_filing'];

    /**
     * Records in the period, oldest first.
     *
     * @return Collection<int, Transaction>
     */
    public function inPeriod(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Transaction::query()
            ->where('occurred_on', '>=', $from->toDateString())
            ->where('occurred_on', '<', $to->addDay()->toDateString())
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Filters  $filters
     * @return Collection<int, Transaction>
     */
    public function apply(Collection $transactions, array $filters, CarbonImmutable $today): Collection
    {
        $filed = $this->filingDates($transactions);

        return $transactions->filter(fn (Transaction $t) => $this->matches($t, $filters, $today, $filed))->values();
    }

    /**
     * How many records each quick filter would leave.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<string, int>
     */
    public function flagCounts(Collection $transactions, CarbonImmutable $today): array
    {
        $filed = $this->filingDates($transactions);

        return collect(self::FLAGS)
            ->mapWithKeys(fn (string $flag) => [$flag => $transactions->filter(fn (Transaction $t) => $this->flagged($t, $flag, $today, $filed))->count()])
            ->all();
    }

    /**
     * @param  Filters  $filters
     * @param  array<string, CarbonImmutable>  $filed
     */
    private function matches(Transaction $transaction, array $filters, CarbonImmutable $today, array $filed): bool
    {
        $quarter = $filters['quarter'] ?? null;
        $type = $filters['type'] ?? null;
        $flag = $filters['flag'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        if ($quarter !== null && $quarter !== '' && $transaction->quarter() !== (int) $quarter) {
            return false;
        }

        if ($type === 'withheld' && $transaction->certificateStatus($today) === CertificateStatus::NotNeeded) {
            return false;
        }

        if ($type !== null && $type !== 'withheld' && $transaction->type->value !== $type) {
            return false;
        }

        if ($flag !== null && ! $this->flagged($transaction, $flag, $today, $filed)) {
            return false;
        }

        if ($search === '') {
            return true;
        }

        $number = (float) preg_replace('/[^\d.]/', '', $search);

        return Str::contains($transaction->counterparty, $search, ignoreCase: true)
            || Str::contains((string) $transaction->invoice_number, $search, ignoreCase: true)
            || Str::contains((string) $transaction->description, $search, ignoreCase: true)
            || ($number > 0 && (float) $transaction->amount === $number);
    }

    /**
     * @param  array<string, CarbonImmutable>  $filed
     */
    private function flagged(Transaction $transaction, string $flag, CarbonImmutable $today, array $filed): bool
    {
        return match ($flag) {
            // Withheld, and the client's 2307 isn't in yet, late or not.
            'missing_2307' => (float) $transaction->tax_withheld > 0 && $transaction->certificate_received_on === null,
            // Money received that no invoice number ties to.
            'no_invoice' => $transaction->type === TransactionType::Payment && blank($transaction->invoice_number),
            // Recorded after its quarter's return was filed: the return doesn't include it.
            'after_filing' => isset($filed[$this->periodKey($transaction)])
                && $transaction->created_at !== null
                && $transaction->created_at->greaterThan($filed[$this->periodKey($transaction)]->endOfDay()),
            default => true,
        };
    }

    /**
     * When each income tax return covering these records was filed.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<string, CarbonImmutable>
     */
    private function filingDates(Collection $transactions): array
    {
        $years = $transactions->map(fn (Transaction $t) => $t->occurred_on->year)->unique()->values()->all();

        return Filing::query()->whereIn('year', $years)->whereIn('form', ReturnForm::INCOME_TAX_FORMS)->get()
            ->mapWithKeys(fn (Filing $f) => ["{$f->year}-{$f->quarter}" => $f->filed_on])
            ->all();
    }

    private function periodKey(Transaction $transaction): string
    {
        return $transaction->occurred_on->year.'-'.$transaction->quarter();
    }
}
