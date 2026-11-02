<?php

namespace App\Tax\Services;

/**
 * Which parts of the rule pack, and which rules, a tax professional has
 * signed off: who, when, and against what. The pack counts as reviewed
 * once every part is; anything without a sign-off is shown as not yet
 * reviewed.
 *
 * @phpstan-type SignOff array{by: string, on: string, source: string}
 * @phpstan-type Part array{key: string, title: string, version: string, reviewed: SignOff|null}
 */
class RuleReview
{
    /**
     * @return list<Part>
     */
    public static function parts(): array
    {
        $parts = [];

        foreach ((array) config('tax.parts') as $key => $part) {
            $parts[] = ['key' => (string) $key, 'title' => (string) $part['title'], 'version' => (string) $part['version'], 'reviewed' => self::signOff($part['reviewed'] ?? null)];
        }

        return $parts;
    }

    /**
     * Every cited rule's sign-off, by id.
     *
     * @return array<string, SignOff|null>
     */
    public static function rules(): array
    {
        return array_map(fn (array $rule) => self::signOff($rule['reviewed'] ?? null), (array) config('tax.rules'));
    }

    public static function packReviewed(): bool
    {
        $parts = self::parts();

        return $parts !== [] && array_filter($parts, fn (array $part) => $part['reviewed'] === null) === [];
    }

    /**
     * A sign-off only counts with all three: who, a date, and the source.
     *
     * @return SignOff|null
     */
    public static function signOff(mixed $record): ?array
    {
        if (! is_array($record)) {
            return null;
        }

        $by = trim((string) ($record['by'] ?? ''));
        $on = (string) ($record['on'] ?? '');
        $source = trim((string) ($record['source'] ?? ''));

        return $by !== '' && $source !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $on) === 1
            ? ['by' => $by, 'on' => $on, 'source' => $source]
            : null;
    }
}
