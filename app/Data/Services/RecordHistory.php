<?php

namespace App\Data\Services;

use App\Data\Models\RecordChange;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use Carbon\CarbonImmutable;

/**
 * Reads the history of changes: what was changed after a return was filed
 * in the records it was computed from.
 *
 * @phpstan-type Change array{at: string, event: string, subject: string, label: string, cause: string, fields: list<array{field: string, before: string|null, after: string|null}>}
 */
class RecordHistory
{
    /** Where a change came from, in words; anything else is "In the app". */
    private const CAUSES = [
        'transactions.store' => 'Added on Transactions',
        'transactions.update' => 'Edited on Transactions',
        'transactions.destroy' => 'Deleted on Transactions',
        'transactions.import.confirm' => 'Bank statement import',
        'transactions.marketplace.confirm' => 'Marketplace import',
        'certificates.store' => 'Added a 2307',
        'certificates.update' => 'Edited a 2307',
        'certificates.destroy' => 'Deleted a 2307',
        'filings.store' => 'Marked filed',
        'filings.destroy' => 'Unmarked filed',
        'backups.restore' => 'Restored a backup',
        'console' => 'Outside the app',
    ];

    /**
     * Changes made after a return was marked filed, to what it was computed
     * from: the sales, expenses and 2307s of its period (an income tax
     * return's is the year to the quarter's end), the year's tax choices,
     * and the profile. Oldest first.
     *
     * @return list<Change>
     */
    public function sinceFiling(Filing $filing): array
    {
        [$start, $end] = $this->period($filing);
        $since = $filing->created_at ?? $filing->filed_on->startOfDay();

        return array_values(RecordChange::query()
            ->where('at', '>', $since)
            ->whereIn('subject', ['transaction', 'certificate', 'tax_profile', 'tax_year', 'backup'])
            ->orderBy('at')->orderBy('id')
            ->get()
            ->filter(fn (RecordChange $change) => $this->touches($change, $filing->year, $start, $end))
            ->map(fn (RecordChange $change) => $this->describe($change))
            ->all());
    }

    /**
     * @return array{string, string} first and last day, as Y-m-d
     */
    private function period(Filing $filing): array
    {
        $year = $filing->year;

        return match (true) {
            $filing->month > 0 => [CarbonImmutable::create($year, $filing->month)->toDateString(), CarbonImmutable::create($year, $filing->month)->endOfMonth()->toDateString()],
            in_array($filing->form, ['1604-C', '1604-E'], true) => ["{$year}-01-01", "{$year}-12-31"],
            // Income tax returns are cumulative: the year so far.
            in_array($filing->form, ReturnForm::INCOME_TAX_FORMS, true) => ["{$year}-01-01", CarbonImmutable::create($year, $filing->quarter * 3)->endOfMonth()->toDateString()],
            default => [CarbonImmutable::create($year, $filing->quarter * 3 - 2)->toDateString(), CarbonImmutable::create($year, $filing->quarter * 3)->endOfMonth()->toDateString()],
        };
    }

    private function touches(RecordChange $change, int $year, string $start, string $end): bool
    {
        $values = [...($change->before ?? []), ...($change->after ?? [])];
        $versions = [$change->before ?? [], $change->after ?? []];

        return match ($change->subject) {
            'transaction' => collect($versions)->contains(fn (array $v) => collect(['occurred_on', 'invoice_paid_on'])
                ->contains(fn (string $field) => isset($v[$field]) && substr((string) $v[$field], 0, 10) >= $start && substr((string) $v[$field], 0, 10) <= $end)),
            'certificate', 'tax_year' => collect($versions)->contains(fn (array $v) => (int) ($v['year'] ?? 0) === $year),
            default => $values !== [] || $change->subject === 'backup',
        };
    }

    /**
     * @return Change
     */
    private function describe(RecordChange $change): array
    {
        $before = $change->before ?? [];
        $after = $change->after ?? [];
        $fields = [];

        // An edit lists only the fields it changed; the record as it was is kept for context.
        $changed = $change->event === 'updated' ? array_keys($after) : array_keys($before + $after);

        foreach ($changed as $field) {
            if ($field === 'id') {
                continue;
            }

            $fields[] = ['field' => (string) $field, 'before' => $this->value($before[$field] ?? null), 'after' => $this->value($after[$field] ?? null)];
        }

        return [
            'at' => $change->at->toIso8601String(),
            'event' => $change->event,
            'subject' => $change->subject,
            'label' => $change->label,
            'cause' => self::CAUSES[$change->cause ?? ''] ?? 'In the app',
            'fields' => $fields,
        ];
    }

    private function value(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'yes' : 'no',
            is_scalar($value) => preg_replace('/ 00:00:00$/', '', (string) $value),
            default => (string) json_encode($value),
        };
    }
}
