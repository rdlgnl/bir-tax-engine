<?php

namespace App\Returns\Services;

use App\Profiles\Models\TaxProfile;
use App\Tax\Services\VatCalculator;
use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * The Summary List of Sales and Purchases for one quarter: the customers
 * and suppliers behind the 2550Q, which backs the input VAT it claims.
 */
class Slsp
{
    public function __construct(private VatCalculator $vat) {}

    /**
     * @return array{sales: list<array{name: string, tin: string|null, invoices: int, vatable: float, zero_rated: float, exempt: float, output_vat: float}>, purchases: list<array{name: string, tin: string|null, invoices: int, amount: float, input_vat: float}>, excluded: list<array{name: string, date: string, amount: float, vat: float}>}
     */
    public function lists(int $year, int $quarter): array
    {
        return $this->vat->lists($year, $quarter);
    }

    public function fileName(TaxProfile $profile, string $kind, int $year, int $quarter): ?string
    {
        $tin = $this->digits($profile->tin);

        if ($tin === null) {
            return null;
        }

        return strtr((string) config('tax.slsp.file_name'), [
            '{tin}' => $tin,
            '{kind}' => $kind === 'sales' ? 'S' : 'P',
            '{period}' => $this->periodEnd($year, $quarter)->format('mY'),
        ]);
    }

    /**
     * The RELIEF data file for the sales or the purchases list.
     */
    public function dat(TaxProfile $profile, string $kind, int $year, int $quarter): string
    {
        $lists = $this->lists($year, $quarter);
        $name = $profile->nameParts();
        $period = $this->periodEnd($year, $quarter)->format('m/d/Y');
        $owner = (string) $this->digits($profile->tin);
        $address = Str::upper((string) $profile->registered_address);
        $people = [
            $this->text($profile->trade_name ?? ''), $this->text($name['last'] ?? (string) $profile->full_name),
            $this->text($name['first'] ?? ''), $this->text($name['middle'] ?? ''),
        ];

        if ($kind === 'sales') {
            $rows = $lists['sales'];
            $lines = [$this->line(['H', 'S', $this->text($owner), ...$people, $this->text((string) $profile->trade_name), $this->text($address), $this->text(''),
                $this->amount(array_sum(array_column($rows, 'exempt'))), $this->amount(array_sum(array_column($rows, 'zero_rated'))),
                $this->amount(array_sum(array_column($rows, 'vatable'))), $this->amount(array_sum(array_column($rows, 'output_vat'))),
                (string) $profile->rdo_code, $period, (string) config('tax.slsp.fiscal_month_end')])];

            foreach ($rows as $row) {
                $lines[] = $this->line(['D', 'S', $this->text((string) $this->digits($row['tin'])), $this->text($row['name']), $this->text(''), $this->text(''), $this->text(''),
                    $this->text(''), $this->text(''), $this->amount($row['exempt']), $this->amount($row['zero_rated']),
                    $this->amount($row['vatable']), $this->amount($row['output_vat']), $owner, $period]);
            }

            return implode("\r\n", $lines)."\r\n";
        }

        $rows = $this->purchaseRows($year, $quarter);
        $lines = [$this->line(['H', 'P', $this->text($owner), ...$people, $this->text((string) $profile->trade_name), $this->text($address), $this->text(''),
            $this->amount(0), $this->amount(0),
            $this->amount(array_sum(array_column($rows, 'services'))), $this->amount(0), $this->amount(array_sum(array_column($rows, 'goods'))),
            $this->amount(array_sum(array_column($rows, 'input_vat'))), $this->amount(array_sum(array_column($rows, 'input_vat'))), $this->amount(0),
            (string) $profile->rdo_code, $period, (string) config('tax.slsp.fiscal_month_end')])];

        foreach ($rows as $row) {
            $lines[] = $this->line(['D', 'P', $this->text((string) $this->digits($row['tin'])), $this->text($row['name']), $this->text(''), $this->text(''), $this->text(''),
                $this->text(''), $this->text(''), $this->amount(0), $this->amount(0),
                $this->amount($row['services']), $this->amount(0), $this->amount($row['goods']), $this->amount($row['input_vat']), $owner, $period]);
        }

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * Suppliers with their purchases split into services and goods.
     *
     * @return list<array{name: string, tin: string|null, services: float, goods: float, input_vat: float}>
     */
    private function purchaseRows(int $year, int $quarter): array
    {
        $purchases = Transaction::query()->forQuarter($year, $quarter)->get()
            ->filter(fn (Transaction $t) => $t->has_valid_invoice && (float) $t->vat_amount > 0 && ! $t->isIncome())
            ->groupBy('counterparty');
        $rows = [];

        foreach ($purchases as $name => $group) {
            $goods = $group->filter(fn (Transaction $t) => $t->category === ExpenseCategory::Equipment);
            $rows[] = [
                'name' => (string) $name,
                'tin' => $group->pluck('counterparty_tin')->filter()->last(),
                'services' => round((float) $group->diff($goods)->sum(fn (Transaction $t) => (float) $t->amount), 2),
                'goods' => round((float) $goods->sum(fn (Transaction $t) => (float) $t->amount), 2),
                'input_vat' => round((float) $group->sum(fn (Transaction $t) => (float) $t->vat_amount), 2),
            ];
        }

        return $rows;
    }

    private function digits(?string $tin): ?string
    {
        $digits = substr((string) preg_replace('/\D/', '', (string) $tin), 0, 9);

        return strlen($digits) === 9 ? $digits : null;
    }

    private function periodEnd(int $year, int $quarter): CarbonImmutable
    {
        return CarbonImmutable::create($year, $quarter * 3)->endOfMonth();
    }

    /**
     * @param  list<string>  $fields
     */
    private function line(array $fields): string
    {
        return implode(',', $fields);
    }

    private function text(string $value): string
    {
        return '"'.Str::of($value)->replaceMatches('/[",\r\n]+/', ' ')->squish()->upper()->value().'"';
    }

    private function amount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
