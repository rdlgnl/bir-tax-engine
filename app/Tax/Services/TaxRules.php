<?php

namespace App\Tax\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

/**
 * The rules in force on a date, from the rule pack's `dated` entries: each
 * entry holds what changed on its date, so a date's rules are every entry
 * on or before it. A year's income tax uses the rules of its last day, a
 * quarter's percentage tax and MCIT those of its first day.
 */
class TaxRules
{
    /** @var array<string, RuleSet> */
    private array $sets = [];

    public function on(CarbonInterface $date): RuleSet
    {
        $day = $date->toDateString();

        return $this->sets[$day] ??= $this->build(CarbonImmutable::parse($day));
    }

    public function forYear(int $year): RuleSet
    {
        return $this->on(CarbonImmutable::create($year, 12, 31));
    }

    public function forQuarter(int $year, int $quarter): RuleSet
    {
        return $this->on(CarbonImmutable::create($year, $quarter * 3 - 2, 1));
    }

    /**
     * The first day the rule pack has rules for.
     */
    public function firstDate(): CarbonImmutable
    {
        return CarbonImmutable::parse((string) array_key_first($this->entries()));
    }

    /**
     * Whether a year can be computed at all: its first day has rules.
     */
    public function covers(int $year): bool
    {
        return CarbonImmutable::create($year)->greaterThanOrEqualTo($this->firstDate());
    }

    private function build(CarbonImmutable $date): RuleSet
    {
        if ($date->lessThan($this->firstDate())) {
            throw new NoTaxRules("BuwisBuddy's tax rules start on {$this->firstDate()->format('F j, Y')}.");
        }

        $values = [];

        foreach ($this->entries() as $effective => $changes) {
            if (CarbonImmutable::parse($effective)->lessThanOrEqualTo($date)) {
                foreach (Arr::dot($changes) as $key => $value) {
                    Arr::set($values, (string) $key, $value);
                }
            }
        }

        // Tables are replaced whole, not merged row by row.
        foreach ($this->entries() as $effective => $changes) {
            foreach (['graduated_brackets', 'compensation_brackets'] as $table) {
                if (CarbonImmutable::parse($effective)->lessThanOrEqualTo($date) && isset($changes[$table])) {
                    $values[$table] = $changes[$table];
                }
            }
        }

        return new RuleSet($date, $values);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function entries(): array
    {
        $entries = config('tax.dated');
        ksort($entries);

        return $entries;
    }
}
