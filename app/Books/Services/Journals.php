<?php

namespace App\Books\Services;

use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Illuminate\Support\Str;

/**
 * The month's cash receipts and cash disbursements journals, the two books a
 * freelancer on cash basis keeps. Receipts are money that actually came in:
 * payments, and invoices marked paid. An unpaid invoice waits until it is.
 *
 * @phpstan-type Receipt array{id: int, occurred_on: string, counterparty: string, invoice_number: string|null, amount: float, withheld: float, received: float}
 * @phpstan-type Disbursement array{id: int, occurred_on: string, counterparty: string, receipt_number: string|null, category: string|null, amount: float}
 */
class Journals
{
    /**
     * @return array{
     *     receipts: list<Receipt>,
     *     disbursements: list<Disbursement>,
     *     totals: array{amount: float, withheld: float, received: float, disbursed: float},
     * }
     */
    public function forMonth(int $year, int $month): array
    {
        $transactions = Transaction::query()
            ->forMonth($year, $month)
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();

        $receipts = array_values($transactions
            ->filter(fn (Transaction $t) => $t->type === TransactionType::Payment
                || ($t->type === TransactionType::Invoice && $t->invoice_paid))
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'occurred_on' => $t->occurred_on->toDateString(),
                'counterparty' => $t->counterparty,
                'invoice_number' => $t->invoice_number,
                'amount' => (float) $t->amount,
                'withheld' => (float) $t->tax_withheld,
                'received' => round((float) $t->amount - (float) $t->tax_withheld, 2),
            ])
            ->all());

        $disbursements = array_values($transactions
            ->filter(fn (Transaction $t) => $t->type === TransactionType::Expense)
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'occurred_on' => $t->occurred_on->toDateString(),
                'counterparty' => $t->counterparty,
                // Expenses keep the supplier's receipt number where invoices keep theirs.
                'receipt_number' => $t->invoice_number,
                'category' => $t->category ? Str::ucfirst($t->category->label()) : null,
                'amount' => (float) $t->amount,
            ])
            ->all());

        return [
            'receipts' => $receipts,
            'disbursements' => $disbursements,
            'totals' => [
                'amount' => round(array_sum(array_column($receipts, 'amount')), 2),
                'withheld' => round(array_sum(array_column($receipts, 'withheld')), 2),
                'received' => round(array_sum(array_column($receipts, 'received')), 2),
                'disbursed' => round(array_sum(array_column($disbursements, 'amount')), 2),
            ],
        ];
    }
}
