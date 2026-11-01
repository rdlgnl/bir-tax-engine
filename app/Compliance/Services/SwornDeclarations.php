<?php

namespace App\Compliance\Services;

use App\Compliance\Models\Certificate;
use App\Profiles\Enums\Profession;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\FilingCalendar;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * The income payee's sworn declaration of gross receipts (RR 11-2018): each
 * January the taxpayer tells every client who withholds that last year's
 * receipts stayed at or under ₱3,000,000, so the client withholds 5% on
 * fees instead of 10%.
 *
 * @phpstan-type Client array{key: string, name: string, rate: float}
 */
class SwornDeclarations
{
    public function __construct(private FilingCalendar $calendar) {}

    /**
     * The year whose receipts are declared: the one just ended in early
     * January, otherwise the one in progress.
     */
    public function defaultYear(CarbonImmutable $today): int
    {
        return $today->year - ($today->lessThan($this->calendar->swornDeclarationDue($today->year)->addDay()) ? 1 : 0);
    }

    /**
     * Clients who withheld tax in the year, named as on their 2307 when one
     * is in, with the share of the fees they withheld.
     *
     * @return list<Client>
     */
    public function clients(int $year): array
    {
        $names = Certificate::query()->where('year', $year)->pluck('payor_name', 'payor_name')->all();

        return array_values(Transaction::query()->forYear($year)->where('tax_withheld', '>', 0)->get()
            ->groupBy('counterparty')
            ->map(function ($payments, string $client) use ($names) {
                $amount = (float) $payments->sum('amount');

                return [
                    'key' => Str::slug($client),
                    'name' => $names[$client] ?? $client,
                    'rate' => $amount > 0 ? round((float) $payments->sum('tax_withheld') / $amount * 100, 1) : 0.0,
                ];
            })
            ->sortBy('name')
            ->values()
            ->all());
    }

    /**
     * Clients who paid in full all year: no declaration needed unless they
     * start withholding.
     *
     * @return list<string>
     */
    public function paidInFull(int $year): array
    {
        $income = Transaction::query()->forYear($year)->where('type', '!=', 'expense')->get()->groupBy('counterparty');

        return array_values($income->filter(fn ($payments) => (float) $payments->sum('tax_withheld') <= 0)->keys()->sort()->values()->all());
    }

    /**
     * What every declaration for the year says, apart from the client.
     *
     * @return array{year: int, name: string, tin: string|null, rdo: string|null, vat: bool, gross: float, over_limit: bool, limit: float, fees: string, rate: float, rate_without: float, due: string}
     */
    public function declaration(TaxProfile $profile, int $year, float $gross): array
    {
        $name = $profile->nameParts();
        $limit = (float) config('tax.eight_percent.gross_limit');
        $overLimit = $gross > $limit;

        return [
            'year' => $year,
            'name' => Str::upper($name['last'] === null ? trim((string) $profile->full_name) : Str::squish("{$name['first']} {$name['middle']} {$name['last']}")),
            'tin' => $profile->tin,
            'rdo' => $profile->rdo_code,
            'vat' => $profile->forYear($year)->vat_registered,
            'gross' => $gross,
            'over_limit' => $overLimit,
            'limit' => $limit,
            'fees' => $profile->profession !== null && $profile->profession !== Profession::None ? 'professional fees' : 'fees for my services',
            'rate' => (float) config('tax.expected_withholding.services') * 100,
            'rate_without' => (float) config('tax.expected_withholding.services_without_declaration') * 100,
            'due' => $this->calendar->swornDeclarationDue($year + 1)->toDateString(),
        ];
    }
}
