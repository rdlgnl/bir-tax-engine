<?php

namespace App\Tax\Services;

use App\Compliance\Services\TaxObligations;
use App\Data\Models\Setting;
use App\Notices\Services\Notices;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;

/**
 * The rule pack in use, and what a new one changed. Packs arrive with app
 * updates and apply at once; filed returns keep the figures they were filed
 * with. To show a change's effect, Home keeps a note of the taxpayer's key
 * numbers under the pack last seen.
 *
 * @phpstan-type Numbers array{quarter_label: string, quarter: float|null, annual: float|null, next_deadline: string|null}
 * @phpstan-type Seen array{version: string, numbers: Numbers}
 */
class RulePacks
{
    public function __construct(
        private TaxCalculationService $engine,
        private TaxObligations $obligations,
        private Notices $notices,
    ) {}

    public function version(): string
    {
        return (string) config('tax.version');
    }

    /**
     * @return list<array{area: string, title: string, summary: string}>
     */
    public function changes(?string $version = null): array
    {
        // Versions contain dots, so look them up in the array, not by config path.
        return array_values((array) (config('tax.changes')[$version ?? $this->version()] ?? []));
    }

    /**
     * @return Seen|null
     */
    public function seen(): ?array
    {
        $seen = Setting::get('rule_pack_seen');

        if (! is_array($seen) || ! is_string($seen['version'] ?? null)) {
            return null;
        }

        $numbers = is_array($seen['numbers'] ?? null) ? $seen['numbers'] : [];
        $amount = fn (string $key) => is_numeric($numbers[$key] ?? null) ? (float) $numbers[$key] : null;

        return [
            'version' => $seen['version'],
            'numbers' => [
                'quarter_label' => is_string($numbers['quarter_label'] ?? null) ? $numbers['quarter_label'] : '',
                'quarter' => $amount('quarter'),
                'annual' => $amount('annual'),
                'next_deadline' => is_string($numbers['next_deadline'] ?? null) ? $numbers['next_deadline'] : null,
            ],
        ];
    }

    /**
     * A pack newer than the one the taxpayer last saw, not yet looked at.
     */
    public function pending(): bool
    {
        $seen = $this->seen();

        return $seen !== null && $seen['version'] !== $this->version();
    }

    /**
     * Keeps the note of today's numbers current while no new pack waits,
     * so "before" is as recent as possible when one arrives.
     */
    public function remember(TaxProfile $profile, CarbonImmutable $today): void
    {
        if (! $this->pending()) {
            Setting::put('rule_pack_seen', ['version' => $this->version(), 'numbers' => $this->numbers($profile, $today)]);
        }
    }

    /**
     * The taxpayer has seen what the new pack changed.
     */
    public function acknowledge(TaxProfile $profile, CarbonImmutable $today): void
    {
        $before = $this->seen()['numbers'] ?? null;
        $after = $this->numbers($profile, $today);
        $same = $before !== null && $before['quarter'] === $after['quarter'] && $before['annual'] === $after['annual']
            && $before['next_deadline'] === $after['next_deadline'];

        $this->notices->record('rules', "Rule pack {$this->version()} installed · ".($same ? 'no change to your numbers' : 'your numbers changed'),
            null, route('rules', absolute: false));
        Setting::put('rule_pack_seen', ['version' => $this->version(), 'numbers' => $after]);
    }

    /**
     * @return Numbers
     */
    public function numbers(TaxProfile $profile, CarbonImmutable $today): array
    {
        $year = $this->engine->forYear($profile, $today->year, $today);
        $ok = $year['status'] === 'ok';
        $quarter = $year['quarters'][$today->quarter] ?? null;
        $next = $this->obligations->nextPayment($profile, $year, $today);

        return [
            'quarter_label' => $today->quarter === 4 ? 'Q4 / annual' : "Q{$today->quarter}",
            'quarter' => $ok && $quarter ? (float) $quarter['due'] : null,
            'annual' => $ok ? (float) $year['quarters'][4]['due'] : null,
            'next_deadline' => $next['quarter']['due_date'] ?? $next['next_deadline'],
        ];
    }
}
