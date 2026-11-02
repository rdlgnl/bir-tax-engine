<?php

namespace App\Tax\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

/**
 * The rules in force on one date. Asking for a rule the date doesn't have
 * (corporate rates before 2021, say) refuses rather than guesses.
 */
final class RuleSet
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(public readonly CarbonImmutable $date, private array $values) {}

    public function get(string $key): mixed
    {
        if (! Arr::has($this->values, $key)) {
            throw new NoTaxRules("BuwisBuddy has no rule for {$key} on {$this->date->format('F j, Y')}.");
        }

        return Arr::get($this->values, $key);
    }

    /**
     * Every rule in force on the date, as the rule pack has them.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->values;
    }

    public function has(string $key): bool
    {
        return Arr::has($this->values, $key);
    }

    public function rate(string $key): float
    {
        return (float) $this->get($key);
    }

    /**
     * The graduated table, or another table of the same shape.
     *
     * @return list<array{int|float, int|float, float}>
     */
    public function brackets(string $table = 'graduated_brackets'): array
    {
        return $this->get($table);
    }

    /**
     * The rule an explanation cites for a part of the computation.
     */
    public function ruleId(string $part): string
    {
        return (string) $this->get("rule_ids.{$part}");
    }
}
