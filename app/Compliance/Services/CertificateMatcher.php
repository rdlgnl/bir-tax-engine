<?php

namespace App\Compliance\Services;

use App\Transactions\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds the payments a client's 2307 covers: same client, same quarter, tax
 * withheld, and not already covered by another certificate.
 *
 * Names must match exactly once punctuation and company suffixes are set
 * aside ("Lumen Digital Inc." is "Lumen Digital"), so "Acme Ventures" never
 * confirms a credit from "Acme Co.".
 */
class CertificateMatcher
{
    /** Words that don't tell two company names apart. */
    private const SUFFIXES = ['inc', 'incorporated', 'corp', 'corporation', 'co', 'company', 'ltd', 'limited', 'opc', 'llc'];

    /**
     * @return array{status: 'exact'|'partial'|'none', payments: Collection<int, Transaction>, withheld: float}
     */
    public function match(string $payorName, int $year, int $quarter, float $taxWithheld): array
    {
        $name = $this->normalize($payorName);

        $payments = $name === '' ? collect() : Transaction::query()
            ->forQuarter($year, $quarter)
            ->where('tax_withheld', '>', 0)
            ->whereNull('certificate_id')
            ->orderBy('occurred_on')
            ->get()
            ->filter(fn (Transaction $t) => $this->normalize($t->counterparty) === $name)
            ->values();

        $withheld = round((float) $payments->sum('tax_withheld'), 2);

        return [
            'status' => match (true) {
                $payments->isEmpty() => 'none',
                abs($withheld - $taxWithheld) < 0.005 => 'exact',
                default => 'partial',
            },
            'payments' => $payments,
            'withheld' => $withheld,
        ];
    }

    private function normalize(string $name): string
    {
        $words = Str::of($name)->lower()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->explode(' ');

        return $words->reject(fn (string $word) => in_array($word, self::SUFFIXES, true))->implode(' ');
    }
}
