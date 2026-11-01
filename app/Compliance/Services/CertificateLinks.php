<?php

namespace App\Compliance\Services;

use App\Compliance\Models\Certificate;
use App\Notices\Services\Notices;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Keeps each 2307 linked to the payments it covers as those payments
 * change. A 2307 saved before its payment was recorded, or one that didn't
 * match, links once the amounts agree; a link whose payments no longer add
 * up to the certificate comes undone, so the credit isn't confirmed by
 * mistake. A 2307 the taxpayer linked by hand stays linked.
 */
class CertificateLinks
{
    private const FIELDS = ['type', 'counterparty', 'occurred_on', 'amount', 'tax_withheld'];

    public function __construct(private CertificateMatcher $matcher, private Notices $notices) {}

    /**
     * After a payment is added, changed or deleted: every 2307 it touched,
     * before and after the change.
     */
    public function afterChange(Transaction $transaction): void
    {
        $changed = ! $transaction->exists || $transaction->wasRecentlyCreated || $transaction->wasChanged(self::FIELDS);

        if (! $changed) {
            return;
        }

        $previous = $transaction->getPrevious();
        $certificates = collect([$previous['certificate_id'] ?? $transaction->certificate_id])
            ->filter()
            ->map(fn (int $id) => Certificate::query()->find($id))
            ->filter();

        foreach ([
            [$previous['counterparty'] ?? $transaction->counterparty, CarbonImmutable::parse($previous['occurred_on'] ?? $transaction->occurred_on)],
            [$transaction->counterparty, $transaction->occurred_on],
        ] as [$name, $date]) {
            $certificates = $certificates->merge($this->forClient((string) $name, $date->year, $date->quarter));
        }

        foreach ($certificates->unique('id') as $certificate) {
            $this->reconcile($certificate);
        }
    }

    /**
     * Links a 2307 to the client's payments in its quarter although the
     * amounts differ. The credit stays what the payments show.
     */
    public function linkByHand(Certificate $certificate): int
    {
        $match = $this->matcher->match($certificate->payor_name, $certificate->year, $certificate->quarter, (float) $certificate->tax_withheld);

        if ($match['payments']->isEmpty()) {
            return 0;
        }

        $certificate->forceFill(['linked_by_hand' => true])->save();
        $this->link($certificate, $match['payments']);

        return $match['payments']->count();
    }

    /**
     * A 2307 linked to payments that no longer add up to it comes undone;
     * one with no payments links to those that match it exactly.
     */
    public function reconcile(Certificate $certificate): void
    {
        $linked = $certificate->transactions()->get();
        $client = $this->matcher->normalize($certificate->payor_name);
        $stray = $linked->reject(fn (Transaction $t) => $this->matcher->normalize($t->counterparty) === $client
            && $t->occurred_on->year === $certificate->year
            && $t->quarter() === $certificate->quarter
            && (float) $t->tax_withheld > 0);

        if ($certificate->linked_by_hand) {
            // Linked by hand: only payments moved to another client or quarter come off.
            $this->unlink($stray);

            return;
        }

        if ($linked->isNotEmpty() && ($stray->isNotEmpty() || abs((float) $linked->sum('tax_withheld') - (float) $certificate->tax_withheld) >= 0.005)) {
            $this->unlink($linked);
            $linked = collect();
        }

        if ($linked->isNotEmpty()) {
            return;
        }

        $match = $this->matcher->match($certificate->payor_name, $certificate->year, $certificate->quarter, (float) $certificate->tax_withheld);

        if ($match['status'] === 'exact') {
            $this->link($certificate, $match['payments']);
            $this->notices->record('certificate', "2307 from {$certificate->payor_name} matched",
                "Q{$certificate->quarter} {$certificate->year} · ₱".Number::format((float) $certificate->tax_withheld, maxPrecision: 2).' withheld, now confirmed',
                route('compliance.show', absolute: false));
        }
    }

    /**
     * The client's 2307s for a quarter, linked or not.
     *
     * @return Collection<int, Certificate>
     */
    private function forClient(string $client, int $year, int $quarter): Collection
    {
        $name = $this->matcher->normalize($client);

        return Certificate::query()
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->get()
            ->filter(fn (Certificate $c) => $this->matcher->normalize($c->payor_name) === $name)
            ->values();
    }

    /**
     * @param  Collection<int, Transaction>  $payments
     */
    private function link(Certificate $certificate, Collection $payments): void
    {
        // Without events: these writes don't change what the links depend on.
        Transaction::query()->whereKey($payments->pluck('id')->all())->update([
            'certificate_id' => $certificate->id,
            'certificate_received_on' => ($certificate->created_at ?? CarbonImmutable::now())->toDateString(),
        ]);
    }

    /**
     * @param  Collection<int, Transaction>  $payments
     */
    private function unlink(Collection $payments): void
    {
        if ($payments->isNotEmpty()) {
            Transaction::query()->whereKey($payments->pluck('id')->all())->update([
                'certificate_id' => null,
                'certificate_received_on' => null,
            ]);
        }
    }
}
