<?php

namespace App\Books\Services;

use App\Loans\Models\Loan;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Enums\BooksMethod;
use App\Tax\Services\DocumentaryStampTax;
use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * The general journal and general ledger, which loose-leaf books bind
 * with the cash journals. Every receipt and expense is a double entry; so
 * is each tax paid with a return or a loan's Form 2000, and the tax
 * withheld from salaries. The ledger posts the entries to each account.
 *
 * An invoice follows the books' method of the year it's dated: on cash
 * books it's income when paid; on accrual books it's income on its date,
 * owed as accounts receivable until paid.
 *
 * Amounts are kept net of VAT, so VAT on a sale or purchase goes to VAT
 * payable and the cash includes it. Salaries are recorded as expenses at
 * their full pay, so the tax withheld from them is cash kept and owed.
 * Balances carry over from the first records in the app; at each year end
 * income, expenses and drawings close into owner's capital. Money held
 * before the first record isn't known, so it's added by hand.
 * REVIEW: the chart of accounts, income tax paid as owner's drawings, and
 * the payroll withholding entries.
 *
 * @phpstan-type Line array{account: int, debit: float, credit: float}
 * @phpstan-type Entry array{ref: string, date: string, description: string, lines: list<Line>}
 * @phpstan-type Posting array{ref: string, date: string, description: string, debit: float, credit: float, balance: float}
 * @phpstan-type Account array{code: int, name: string, opening: float, debits: float, credits: float, closing: float, postings: list<Posting>}
 */
class GeneralLedger
{
    /**
     * The chart of accounts: code => [name, normal balance].
     */
    public const ACCOUNTS = [
        101 => ['Cash', 'debit'],
        103 => ['Accounts receivable', 'debit'],
        105 => ['Creditable withholding tax', 'debit'],
        201 => ['Withholding tax payable', 'credit'],
        202 => ['Withholding tax payable on compensation', 'credit'],
        205 => ['VAT payable', 'credit'],
        301 => ["Owner's capital", 'credit'],
        302 => ["Owner's drawings", 'debit'],
        401 => ['Service income', 'credit'],
        510 => ['Internet and phone', 'debit'],
        511 => ['Rent', 'debit'],
        512 => ['Equipment', 'debit'],
        513 => ['Software', 'debit'],
        514 => ['Transportation and travel', 'debit'],
        515 => ['Professional and contract fees', 'debit'],
        519 => ['Other expenses', 'debit'],
        520 => ['Taxes and licenses', 'debit'],
        521 => ['Interest and penalties', 'debit'],
    ];

    private const EXPENSE_ACCOUNTS = [
        'internet' => 510,
        'rent' => 511,
        'equipment' => 512,
        'software' => 513,
        'transport' => 514,
        'person' => 515,
        'other' => 519,
    ];

    private const INCOME_TAX_FORMS = ['1701Q', '1701', '1701A', '1701-MS'];

    private const WITHHOLDING_FORMS = ['0619-E', '1601-EQ'];

    /** Accounts from here on are the year's: they close into owner's capital. */
    private const FIRST_NOMINAL = 302;

    /** @var array<int, list<array{month: int, withheld: float, refund: float}>> payroll months by year */
    private array $payroll = [];

    /** @var array<int, BooksMethod> each year's books method */
    private array $methods = [];

    public function __construct(private Payroll $payrollService, private DocumentaryStampTax $dst) {}

    /**
     * The month's general journal, in date order.
     *
     * @return list<Entry>
     */
    public function journal(int $year, int $month): array
    {
        $entries = [];
        // A paid invoice is a receipt in the month it was paid.
        $transactions = Transaction::query()->cashInMonth($year, $month)->orderBy('occurred_on')->orderBy('id')->get()
            ->sortBy(fn (Transaction $t) => $t->cashDate()->toDateString())->values();

        foreach ($transactions as $t) {
            if ($t->type === TransactionType::Expense) {
                $entries[] = $this->expense($t);
            } elseif ($t->type === TransactionType::Invoice && $this->accrual($t)) {
                $entries[] = $this->collection($t);
            } elseif ($t->type === TransactionType::Payment || $t->invoice_paid) {
                $entries[] = $this->receipt($t);
            }
        }

        // On accrual books an invoice is income on its date, paid or not.
        $invoiced = Transaction::query()->forMonth($year, $month)->where('type', TransactionType::Invoice)->orderBy('occurred_on')->orderBy('id')->get();

        foreach ($invoiced as $t) {
            if ($this->accrual($t)) {
                $entries[] = $this->sale($t);
            }
        }

        $filings = Filing::query()
            ->whereYear('paid_on', $year)
            ->whereMonth('paid_on', $month)
            ->where('amount_paid', '>', 0)
            ->orderBy('paid_on')
            ->orderBy('id')
            ->get();

        foreach ($filings as $filing) {
            if (($entry = $this->taxPayment($filing)) !== null) {
                $entries[] = $entry;
            }
        }

        $loans = Loan::query()
            ->where('lender_kind', 'person')
            ->whereYear('filed_on', $year)
            ->whereMonth('filed_on', $month)
            ->where('amount_paid', '>', 0)
            ->orderBy('filed_on')
            ->get();

        foreach ($loans as $loan) {
            $entries[] = $this->stampTax($loan);
        }

        if (($entry = $this->payrollWithholding($year, $month)) !== null) {
            $entries[] = $entry;
        }

        usort($entries, fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        foreach ($entries as $i => $entry) {
            $entries[$i]['ref'] = sprintf('GJ-%d-%02d-%03d', $year, $month, $i + 1);
        }

        return $entries;
    }

    /**
     * Each account's postings for the month, with its balance before and
     * after.
     *
     * @return array{accounts: list<Account>, debits: float, credits: float}
     */
    public function ledger(int $year, int $month): array
    {
        $opening = $this->balancesBefore($year, $month);

        $postings = [];

        foreach ($this->journal($year, $month) as $entry) {
            foreach ($entry['lines'] as $line) {
                $postings[$line['account']][] = ['entry' => $entry, 'line' => $line];
            }
        }

        $accounts = [];
        $debits = $credits = 0.0;

        foreach (self::ACCOUNTS as $code => [$name]) {
            $balance = round($opening[$code] ?? 0.0, 2);

            if ($balance === 0.0 && ! isset($postings[$code])) {
                continue;
            }

            $account = ['code' => $code, 'name' => $name, 'opening' => $balance, 'debits' => 0.0, 'credits' => 0.0, 'closing' => 0.0, 'postings' => []];

            foreach ($postings[$code] ?? [] as ['entry' => $entry, 'line' => $line]) {
                $balance = round($balance + $this->signed($line), 2);
                $account['debits'] = round($account['debits'] + $line['debit'], 2);
                $account['credits'] = round($account['credits'] + $line['credit'], 2);
                $account['postings'][] = [
                    'ref' => $entry['ref'],
                    'date' => $entry['date'],
                    'description' => $entry['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'balance' => $balance,
                ];
            }

            $account['closing'] = $balance;
            $debits += $account['debits'];
            $credits += $account['credits'];
            $accounts[] = $account;
        }

        return ['accounts' => $accounts, 'debits' => round($debits, 2), 'credits' => round($credits, 2)];
    }

    /**
     * Every account's balance at the start of a month, from the first
     * records in the app. Each year end closes the year's income, expenses
     * and drawings into owner's capital.
     *
     * @return array<int, float>
     */
    private function balancesBefore(int $year, int $month): array
    {
        $balances = [];
        $first = collect([
            Transaction::query()->min('occurred_on'),
            Filing::query()->whereNotNull('paid_on')->min('paid_on'),
            Loan::query()->whereNotNull('filed_on')->min('filed_on'),
        ])->filter()->map(fn (string $date) => CarbonImmutable::parse($date)->year)->min();

        for ($y = $first ?? $year; $y <= $year; $y++) {
            for ($m = 1; $m <= ($y < $year ? 12 : $month - 1); $m++) {
                foreach ($this->journal($y, $m) as $entry) {
                    foreach ($entry['lines'] as $line) {
                        $balances[$line['account']] = ($balances[$line['account']] ?? 0.0) + $this->signed($line);
                    }
                }
            }

            if ($y < $year) {
                foreach ($balances as $code => $balance) {
                    if ($code >= self::FIRST_NOMINAL) {
                        // Income adds to capital; expenses and drawings take from it.
                        $balances[301] = ($balances[301] ?? 0.0) + (self::ACCOUNTS[$code][1] === 'credit' ? $balance : -$balance);
                        unset($balances[$code]);
                    }
                }
            }
        }

        return $balances;
    }

    /**
     * Whether an invoice is on accrual books: its year's method.
     */
    private function accrual(Transaction $t): bool
    {
        $year = $t->occurred_on->year;

        return ($this->methods[$year] ??= TaxProfile::current()?->forYear($year)->books_method ?? BooksMethod::Accrual) === BooksMethod::Accrual;
    }

    /**
     * An invoice on accrual books: owed by the client, income on its date.
     *
     * @return Entry
     */
    private function sale(Transaction $t): array
    {
        $amount = (float) $t->amount;
        $vat = (float) $t->vat_amount;

        return $this->entry($t->occurred_on->toDateString(), "Invoiced {$t->counterparty} · invoice {$t->invoice_number}", [
            $this->debit(103, $amount + $vat),
            $this->credit(401, $amount),
            $this->credit(205, $vat),
        ]);
    }

    /**
     * An accrual invoice paid: cash after the client's withholding, the
     * withholding as a credit against income tax, the receivable settled.
     *
     * @return Entry
     */
    private function collection(Transaction $t): array
    {
        $owed = (float) $t->amount + (float) $t->vat_amount;
        $withheld = (float) $t->tax_withheld;

        return $this->entry($t->cashDate()->toDateString(), "Collected from {$t->counterparty} · invoice {$t->invoice_number}", [
            $this->debit(101, $owed - $withheld),
            $this->debit(105, $withheld),
            $this->credit(103, $owed),
        ]);
    }

    /**
     * Money in: cash after the client's withholding, the withholding as a
     * credit against income tax, the income, and any VAT on it.
     *
     * @return Entry
     */
    private function receipt(Transaction $t): array
    {
        $amount = (float) $t->amount;
        $withheld = (float) $t->tax_withheld;
        $vat = (float) $t->vat_amount;

        return $this->entry($t->cashDate()->toDateString(), "Received from {$t->counterparty}".($t->invoice_number ? " · invoice {$t->invoice_number}" : ''), [
            $this->debit(101, $amount + $vat - $withheld),
            $this->debit(105, $withheld),
            $this->credit(401, $amount),
            $this->credit(205, $vat),
        ]);
    }

    /**
     * Money out: the expense and its VAT, cash after what the taxpayer
     * withheld, and that withholding owed to the BIR.
     *
     * @return Entry
     */
    private function expense(Transaction $t): array
    {
        $amount = (float) $t->amount;
        $withheld = (float) $t->agent_withheld;
        $vat = (float) $t->vat_amount;
        $category = $t->category ?? ExpenseCategory::Other;

        return $this->entry($t->occurred_on->toDateString(), Str::ucfirst($category->label())." · {$t->counterparty}", [
            $this->debit(self::EXPENSE_ACCOUNTS[$category->value], $amount),
            $this->debit(205, $vat),
            $this->credit(101, $amount + $vat - $withheld),
            $this->credit(201, $withheld),
        ]);
    }

    /**
     * A tax paid with a return: percentage tax is a business expense,
     * income tax is the owner's, withholding and VAT settle what's owed.
     * Penalties are booked apart. Other returns aren't in these books.
     *
     * @return Entry|null
     */
    private function taxPayment(Filing $filing): ?array
    {
        $account = match (true) {
            $filing->form === '2551Q' => 520,
            $filing->form === '2550Q' => 205,
            in_array($filing->form, self::INCOME_TAX_FORMS, true) => 302,
            in_array($filing->form, self::WITHHOLDING_FORMS, true) => 201,
            $filing->form === '1601-C' => 202,
            default => null,
        };

        if ($account === null || $filing->paid_on === null) {
            return null;
        }

        $paid = (float) $filing->amount_paid;
        $tax = $filing->taxPaid();
        $period = $filing->month > 0
            ? CarbonImmutable::create($filing->year, $filing->month)->format('F Y')
            : ($filing->quarter === 4 && in_array($filing->form, self::INCOME_TAX_FORMS, true) ? (string) $filing->year : "Q{$filing->quarter} {$filing->year}");

        return $this->entry($filing->paid_on->toDateString(), "{$filing->form} paid · {$period}", [
            $this->debit($account, $tax),
            $this->debit(521, round($paid - $tax, 2)),
            $this->credit(101, $paid),
        ]);
    }

    /**
     * Form 2000 the taxpayer paid on a loan: the stamp tax is a business
     * tax, any penalty apart. A bank that files it takes it from the loan.
     *
     * @return Entry
     */
    private function stampTax(Loan $loan): array
    {
        $paid = (float) $loan->amount_paid;
        $tax = min($paid, $this->dst->onLoan((float) $loan->amount, $loan->signed_on, $loan->term_months));

        return $this->entry((string) $loan->filed_on?->toDateString(), "Form 2000 paid · DST on the loan from {$loan->lender}", [
            $this->debit(520, $tax),
            $this->debit(521, round($paid - $tax, 2)),
            $this->credit(101, $paid),
        ]);
    }

    /**
     * The tax withheld from a month's salaries, once the month is over:
     * kept from the pay and owed with the 1601-C. A year-end refund to the
     * employees comes off it.
     *
     * @return Entry|null
     */
    private function payrollWithholding(int $year, int $month): ?array
    {
        $end = CarbonImmutable::create($year, $month)->endOfMonth();

        if ($end->isFuture()) {
            return null;
        }

        $this->payroll[$year] ??= array_map(
            fn (array $m) => ['month' => $m['month'], 'withheld' => $m['withheld'], 'refund' => $m['refund']],
            $this->payrollService->year($year),
        );
        $row = collect($this->payroll[$year])->firstWhere('month', $month);
        $net = $row !== null ? round($row['withheld'] - $row['refund'], 2) : 0.0;

        if ($net === 0.0) {
            return null;
        }

        return $this->entry($end->toDateString(), "Tax withheld from {$end->format('F')} salaries", $net > 0
            ? [$this->debit(101, $net), $this->credit(202, $net)]
            : [$this->debit(202, -$net), $this->credit(101, -$net)]);
    }

    /**
     * @param  list<Line>  $lines
     * @return Entry
     */
    private function entry(string $date, string $description, array $lines): array
    {
        return [
            'ref' => '',
            'date' => $date,
            'description' => $description,
            'lines' => array_values(array_filter($lines, fn (array $line) => $line['debit'] > 0 || $line['credit'] > 0)),
        ];
    }

    /**
     * @return Line
     */
    private function debit(int $account, float $amount): array
    {
        return ['account' => $account, 'debit' => round($amount, 2), 'credit' => 0.0];
    }

    /**
     * @return Line
     */
    private function credit(int $account, float $amount): array
    {
        return ['account' => $account, 'debit' => 0.0, 'credit' => round($amount, 2)];
    }

    /**
     * A line's effect on its account's balance, by the account's normal side.
     *
     * @param  Line  $line
     */
    private function signed(array $line): float
    {
        return self::ACCOUNTS[$line['account']][1] === 'debit'
            ? $line['debit'] - $line['credit']
            : $line['credit'] - $line['debit'];
    }
}
