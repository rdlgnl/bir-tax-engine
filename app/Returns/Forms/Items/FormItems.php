<?php

namespace App\Returns\Forms\Items;

use App\Returns\Services\ReturnForm;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use Illuminate\Support\Str;

/**
 * Turns a return's snapshot into the value of each item on one BIR form,
 * keyed as the form's field map keys them.
 *
 * Items the app has no figure for (penalties, foreign tax credits, the
 * spouse's column) stay blank for the taxpayer to fill in by hand. Totals
 * are added up from the amounts as printed, so the form's own arithmetic
 * holds after rounding to whole pesos.
 *
 * Item keys like "6" are integers to PHP: merge values with `+`, since a
 * spread or array_merge() renumbers them.
 *
 * @phpstan-import-type Snapshot from ReturnForm
 * @phpstan-import-type Taxpayer from ReturnForm
 *
 * @phpstan-type Values array<array-key, string|int|float|bool|null>
 */
abstract class FormItems
{
    /**
     * @param  Snapshot  $snapshot
     * @return Values
     */
    abstract public function values(array $snapshot): array;

    /**
     * The taxpayer's identity, into the boxes the form names for each.
     *
     * @param  Taxpayer  $taxpayer
     * @param  array{tin: array{string, string, string}, rdo: string, name: string, address?: array{string, int, string}, zip?: string, birth_date?: string, email?: string, contact?: string, citizenship?: string}  $items
     * @return Values
     */
    protected function identity(array $taxpayer, array $items): array
    {
        $tin = $this->tinGroups($taxpayer['tin']);
        $values = [
            $items['tin'][0] => $tin[0],
            $items['tin'][1] => $tin[1],
            $items['tin'][2] => $tin[2],
            $items['rdo'] => $taxpayer['rdo'],
            $items['name'] => $this->fullName($taxpayer),
        ];

        if (isset($items['address'])) {
            [$first, $width, $second] = $items['address'];
            [$values[$first], $values[$second]] = $this->wrap($taxpayer['address'] ?? '', $width);
        }

        foreach (['zip', 'email', 'contact', 'citizenship'] as $field) {
            if (isset($items[$field])) {
                $values[$items[$field]] = $field === 'contact'
                    ? preg_replace('/[^\d+]/', '', (string) $taxpayer['contact'])
                    : $taxpayer[$field];
            }
        }

        if (isset($items['birth_date']) && $taxpayer['birth_date'] !== null) {
            // MM/DD/YYYY, one digit per box.
            $values[$items['birth_date']] = date('mdY', (int) strtotime($taxpayer['birth_date']));
        }

        return $values;
    }

    /**
     * The TIN as the later pages' header carries it: nine digits, no dashes.
     * The branch code is printed on the forms already.
     *
     * @param  Taxpayer  $taxpayer
     */
    protected function tinDigits(array $taxpayer): string
    {
        return implode('', $this->tinGroups($taxpayer['tin']));
    }

    /**
     * @param  Taxpayer  $taxpayer
     */
    protected function lastName(array $taxpayer): string
    {
        return $taxpayer['last_name'] ?? $taxpayer['name'];
    }

    /**
     * @param  Snapshot  $snapshot
     */
    protected function eightPercent(array $snapshot): bool
    {
        return $snapshot['regime'] === Regime::EightPercent->value;
    }

    /**
     * @param  Snapshot  $snapshot
     */
    protected function itemized(array $snapshot): bool
    {
        return $snapshot['deduction_method'] === DeductionMethod::Itemized->value;
    }

    /**
     * Whole pesos: 49 centavos or less drop, 50 or more round up.
     */
    protected function peso(float $amount): int
    {
        return (int) round($amount, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * "LAST NAME, FIRST NAME MIDDLE NAME", as the forms ask. Profiles from
     * before the name was split only have the full name.
     *
     * @param  Taxpayer  $taxpayer
     */
    private function fullName(array $taxpayer): string
    {
        if ($taxpayer['last_name'] === null) {
            return $taxpayer['name'];
        }

        return trim("{$taxpayer['last_name']}, {$taxpayer['first_name']} {$taxpayer['middle_name']}");
    }

    /**
     * @return array{string, string, string}
     */
    private function tinGroups(?string $tin): array
    {
        $digits = substr(preg_replace('/\D/', '', (string) $tin), 0, 9);

        return [substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 3)];
    }

    /**
     * An address over two rows of boxes, broken between words.
     *
     * @return array{string, string}
     */
    private function wrap(string $text, int $width): array
    {
        if (mb_strlen($text) <= $width) {
            return [$text, ''];
        }

        $first = Str::of(mb_substr($text, 0, $width + 1))->beforeLast(' ')->trim()->value() ?: mb_substr($text, 0, $width);

        return [$first, trim(mb_substr($text, mb_strlen($first)))];
    }
}
