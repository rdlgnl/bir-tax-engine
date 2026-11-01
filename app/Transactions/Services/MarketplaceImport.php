<?php

namespace App\Transactions\Services;

use App\Returns\Models\Filing;
use App\Tax\Services\TaxRules;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reading an online marketplace's payout report (CSV), a line per order.
 * A sale is what the buyer paid, not the payout: the platform's fees are
 * an expense and the tax it withheld is a credit, confirmed by its 2307.
 *
 * @phpstan-type Mapping array{date: int|null, order: int|null, sales: int|null, fees: int|null, withheld: int|null, payout: int|null}
 * @phpstan-type Order array{line: int, date: string|null, order: string, sales: float|null, fees: float, withheld: float, payout: string|null}
 * @phpstan-type Report array{columns: list<string>, mapping: Mapping, orders: list<Order>, matched: bool}
 * @phpstan-type Sorted array{order: Order, status: 'import'|'duplicate'|'filed'|'invalid'}
 * @phpstan-type Summary array{orders: int, payouts: int, sales: float, fees: float, withheld: float, paid_out: float, to_import: int, duplicates: int, filed: int, invalid: int, import_sales: float, import_fees: float, import_withheld: float}
 */
class MarketplaceImport
{
    public function __construct(private CsvFile $csv, private TaxRules $rules) {}

    /**
     * The report's columns, which of them hold each figure, and every order.
     *
     * @param  Mapping|null  $mapping  the taxpayer's own choice of columns
     * @return Report
     */
    public function read(string $path, ?array $mapping = null): array
    {
        $records = $this->csv->records($path);
        [$headerAt, $guessed] = $this->findHeader($records);
        $columns = array_map(fn ($c) => trim((string) $c), $records[$headerAt] ?? []);
        $mapping ??= $guessed;
        $matched = $mapping['date'] !== null && $mapping['sales'] !== null;
        $cell = fn (array $record, ?int $at) => $at === null ? '' : trim((string) ($record[$at] ?? ''));
        $orders = [];

        foreach (array_slice($records, $headerAt + 1, (int) config('import.max_rows'), true) as $i => $record) {
            if (! $matched || count(array_filter($record, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            // Reports show fees and withholding either as deductions or as positive amounts.
            $orders[] = [
                'line' => $i + 1,
                'date' => $this->csv->date($cell($record, $mapping['date'])),
                'order' => Str::limit($cell($record, $mapping['order']), 60, ''),
                'sales' => $this->csv->money($cell($record, $mapping['sales'])),
                'fees' => abs($this->csv->money($cell($record, $mapping['fees'])) ?? 0.0),
                'withheld' => abs($this->csv->money($cell($record, $mapping['withheld'])) ?? 0.0),
                'payout' => $cell($record, $mapping['payout']) ?: null,
            ];
        }

        return ['columns' => $columns, 'mapping' => $mapping, 'orders' => $orders, 'matched' => $matched];
    }

    /**
     * Which orders are new, already recorded, in a filed quarter, or unreadable.
     *
     * @param  list<Order>  $orders
     * @return list<Sorted>
     */
    public function sort(array $orders, string $platform, CarbonImmutable $today): array
    {
        $recorded = Transaction::query()->where('type', TransactionType::Payment)->where('counterparty', $platform)->get();
        $ids = $recorded->pluck('invoice_number')->filter()->flip();
        $sorted = [];

        foreach ($orders as $order) {
            $status = match (true) {
                $order['date'] === null || $order['sales'] === null || $order['sales'] <= 0
                    || CarbonImmutable::parse($order['date'])->greaterThan($today)
                    || CarbonImmutable::parse($order['date'])->lessThan($this->rules->firstDate()) => 'invalid',
                $order['order'] !== '' ? $ids->has($order['order']) : $recorded->contains(
                    fn (Transaction $t) => $t->occurred_on->toDateString() === $order['date'] && abs((float) $t->amount - (float) $order['sales']) < 0.01,
                ) => 'duplicate',
                Filing::incomeTaxReturnCovering(CarbonImmutable::parse((string) $order['date'])) !== null => 'filed',
                default => 'import',
            };

            $sorted[] = ['order' => $order, 'status' => $status];
        }

        return $sorted;
    }

    /**
     * @param  list<Sorted>  $sorted
     * @return Summary
     */
    public function summary(array $sorted): array
    {
        $readable = array_values(array_filter($sorted, fn (array $s) => $s['status'] !== 'invalid'));
        $new = array_values(array_filter($sorted, fn (array $s) => $s['status'] === 'import'));
        $sum = fn (array $rows, string $key) => round(array_sum(array_map(fn (array $s) => (float) $s['order'][$key], $rows)), 2);
        $count = fn (string $status) => count(array_filter($sorted, fn (array $s) => $s['status'] === $status));
        $sales = $sum($readable, 'sales');
        $fees = $sum($readable, 'fees');
        $withheld = $sum($readable, 'withheld');

        return [
            'orders' => count($readable),
            'payouts' => count(array_unique(array_filter(array_map(fn (array $s) => $s['order']['payout'], $readable)))),
            'sales' => $sales,
            'fees' => $fees,
            'withheld' => $withheld,
            'paid_out' => round($sales - $fees - $withheld, 2),
            'to_import' => count($new),
            'duplicates' => $count('duplicate'),
            'filed' => $count('filed'),
            'invalid' => $count('invalid'),
            'import_sales' => $sum($new, 'sales'),
            'import_fees' => $sum($new, 'fees'),
            'import_withheld' => $sum($new, 'withheld'),
        ];
    }

    /**
     * Records each new order as a sale with the tax withheld from it, and
     * the month's fees on those orders as one expense.
     *
     * @param  list<Sorted>  $sorted
     * @return array{orders: int, sales: float}
     */
    public function import(array $sorted, string $platform): array
    {
        $new = array_values(array_filter($sorted, fn (array $s) => $s['status'] === 'import'));

        DB::transaction(function () use ($new, $platform) {
            $months = [];

            foreach ($new as ['order' => $order]) {
                Transaction::query()->create([
                    'type' => TransactionType::Payment,
                    'occurred_on' => $order['date'],
                    'counterparty' => $platform,
                    'amount' => $order['sales'],
                    'tax_withheld' => $order['withheld'],
                    'invoice_number' => $order['order'] ?: null,
                    'description' => 'Imported order'.($order['order'] ? " {$order['order']}" : ''),
                ]);

                $month = substr((string) $order['date'], 0, 7);
                $months[$month]['fees'] = ($months[$month]['fees'] ?? 0.0) + $order['fees'];
                $months[$month]['last'] = max($months[$month]['last'] ?? '', (string) $order['date']);
            }

            foreach ($months as $month => ['fees' => $fees, 'last' => $last]) {
                if ($fees <= 0) {
                    continue;
                }

                Transaction::query()->create([
                    'type' => TransactionType::Expense,
                    'occurred_on' => $last,
                    'counterparty' => $platform,
                    'amount' => round($fees, 2),
                    'category' => 'other',
                    'description' => 'Platform fees and commissions · '.CarbonImmutable::parse("{$month}-01")->format('F Y'),
                ]);
            }
        });

        return ['orders' => count($new), 'sales' => round(array_sum(array_map(fn (array $s) => (float) $s['order']['sales'], $new)), 2)];
    }

    /**
     * The first of the opening lines whose cells read as column names.
     *
     * @param  list<list<string|null>>  $records
     * @return array{int, Mapping}
     */
    private function findHeader(array $records): array
    {
        $empty = ['date' => null, 'order' => null, 'sales' => null, 'fees' => null, 'withheld' => null, 'payout' => null];

        foreach (array_slice($records, 0, 15, true) as $i => $record) {
            $mapping = $empty;
            $names = array_map(fn ($v) => Str::of((string) $v)->lower()->replaceMatches('/[^a-z ]+/', ' ')->squish()->value(), $record);

            foreach (array_keys($empty) as $field) {
                foreach ((array) config("import.marketplace.columns.{$field}") as $synonym) {
                    $at = array_search($synonym, $names, true);

                    if ($at !== false && ! in_array($at, $mapping, true)) {
                        $mapping[$field] = (int) $at;
                        break;
                    }
                }
            }

            if ($mapping['date'] !== null && $mapping['sales'] !== null) {
                return [$i, $mapping];
            }
        }

        return [0, $empty];
    }
}
