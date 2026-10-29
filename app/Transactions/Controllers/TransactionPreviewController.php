<?php

namespace App\Transactions\Controllers;

use App\Compliance\Models\Filing;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use App\Transactions\Requests\PreviewTransactionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Shows, before saving, what an entry does to the tax and whether it looks
 * like a duplicate or a typo. Called with Inertia's useHttp as the form changes.
 */
class TransactionPreviewController extends Controller
{
    public function __invoke(PreviewTransactionRequest $request, TaxCalculationService $engine): JsonResponse
    {
        $data = $request->safe()->except('id');
        $candidate = $request->filled('id')
            ? Transaction::query()->findOrFail($request->integer('id'))->fill($data)
            : new Transaction($data);
        $date = CarbonImmutable::parse($candidate->occurred_on);

        $year = Transaction::query()->forYear($date->year)->get();
        $preview = $engine->previewEffect(TaxProfile::current(), $year, $candidate);

        return response()->json([
            ...$preview,
            'filed' => Filing::incomeTaxReturnCovering($date) !== null,
            'duplicate' => $this->duplicateOf($candidate, $date),
            'unusual_multiple' => $this->unusualMultiple($candidate, $year),
        ]);
    }

    /**
     * @return array{counterparty: string, amount: float, occurred_on: string}|null
     */
    private function duplicateOf(Transaction $candidate, CarbonImmutable $date): ?array
    {
        if (! $candidate->isIncome() || blank($candidate->counterparty)) {
            return null;
        }

        $match = Transaction::query()
            ->where('type', '!=', TransactionType::Expense)
            ->where('amount', $candidate->amount)
            ->where('occurred_on', '>=', $date->subDays(7)->toDateString())
            ->where('occurred_on', '<', $date->addDays(8)->toDateString())
            ->when($candidate->exists, fn ($query) => $query->whereKeyNot($candidate->getKey()))
            ->get()
            ->first(fn (Transaction $t) => Str::lower(trim($t->counterparty)) === Str::lower(trim($candidate->counterparty)));

        return $match ? [
            'counterparty' => $match->counterparty,
            'amount' => (float) $match->amount,
            'occurred_on' => $match->occurred_on->toDateString(),
        ] : null;
    }

    /**
     * How many times bigger than a usual payment this one is, when it is
     * at least ten times bigger. Catches an extra zero.
     *
     * @param  Collection<int, Transaction>  $year
     */
    private function unusualMultiple(Transaction $candidate, $year): ?int
    {
        $amounts = $year->filter(fn (Transaction $t) => $t->isIncome())->map(fn (Transaction $t) => (float) $t->amount);

        if (! $candidate->isIncome() || $amounts->count() < 5) {
            return null;
        }

        $multiple = (float) $candidate->amount / $amounts->median();

        return $multiple >= 10 ? (int) floor($multiple) : null;
    }
}
