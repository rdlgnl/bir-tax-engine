<?php

namespace App\Transactions\Models;

use App\Compliance\Models\Certificate;
use App\Compliance\Services\CertificateLinks;
use App\Tax\Enums\BooksMethod;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Enums\VatKind;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something that happened to the business: money in, an invoice, or an expense.
 *
 * @property int $id
 * @property TransactionType $type
 * @property CarbonImmutable $occurred_on
 * @property string $counterparty
 * @property string $amount
 * @property string $tax_withheld
 * @property VatKind|null $vat_kind
 * @property string $vat_amount
 * @property string|null $counterparty_tin
 * @property string|null $atc
 * @property string $agent_withheld
 * @property CarbonImmutable|null $certificate_received_on
 * @property int|null $certificate_id
 * @property string|null $invoice_number
 * @property bool $invoice_paid
 * @property CarbonImmutable|null $invoice_paid_on
 * @property ExpenseCategory|null $category
 * @property bool $has_valid_invoice
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'type',
    'occurred_on',
    'counterparty',
    'amount',
    'tax_withheld',
    'vat_kind',
    'vat_amount',
    'counterparty_tin',
    'atc',
    'agent_withheld',
    'certificate_received_on',
    'invoice_number',
    'invoice_paid',
    'invoice_paid_on',
    'category',
    'has_valid_invoice',
    'description',
])]
#[UseFactory(TransactionFactory::class)]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'tax_withheld' => 0,
        'vat_amount' => 0,
        'agent_withheld' => 0,
        'invoice_paid' => false,
        'has_valid_invoice' => false,
    ];

    /**
     * A 2307 follows its payments: however a payment is added, changed or
     * deleted, the client's certificates for the quarter are checked again.
     */
    protected static function booted(): void
    {
        // A paid invoice keeps the day it was paid; without one, its own date.
        static::saving(function (self $transaction) {
            if ($transaction->type !== TransactionType::Invoice || ! $transaction->invoice_paid) {
                $transaction->invoice_paid_on = null;
            } elseif ($transaction->invoice_paid_on === null) {
                $transaction->invoice_paid_on = $transaction->occurred_on;
            }
        });
        static::saved(fn (self $transaction) => app(CertificateLinks::class)->afterChange($transaction));
        static::deleted(fn (self $transaction) => app(CertificateLinks::class)->afterChange($transaction));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'occurred_on' => 'date',
            'amount' => 'decimal:2',
            'tax_withheld' => 'decimal:2',
            'vat_kind' => VatKind::class,
            'vat_amount' => 'decimal:2',
            'agent_withheld' => 'decimal:2',
            'certificate_received_on' => 'date',
            'invoice_paid' => 'boolean',
            'invoice_paid_on' => 'date',
            'category' => ExpenseCategory::class,
            'has_valid_invoice' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forQuarter(Builder $query, int $year, int $quarter): void
    {
        $start = CarbonImmutable::create($year, $quarter * 3 - 2);

        $query->where('occurred_on', '>=', $start->toDateString())
            ->where('occurred_on', '<', $start->addMonths(3)->toDateString());
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forYear(Builder $query, int $year): void
    {
        // Half-open, because SQLite stores dates as "2026-12-31 00:00:00".
        $query->where('occurred_on', '>=', "{$year}-01-01")
            ->where('occurred_on', '<', ($year + 1).'-01-01');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forMonth(Builder $query, int $year, int $month): void
    {
        $start = CarbonImmutable::create($year, $month);

        $query->where('occurred_on', '>=', $start->toDateString())
            ->where('occurred_on', '<', $start->addMonth()->toDateString());
    }

    /**
     * @return BelongsTo<Certificate, $this>
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function quarter(): int
    {
        return $this->occurred_on->quarter;
    }

    public function isIncome(): bool
    {
        return $this->type->isIncome();
    }

    /**
     * The day a sale counts as income on the books' method: an invoice on
     * its date (accrual) or when paid (cash), a payment on its own date.
     * Null for an unpaid invoice on cash, and for anything that isn't a sale.
     */
    public function incomeDate(BooksMethod $method): ?CarbonImmutable
    {
        return match (true) {
            ! $this->isIncome() => null,
            $this->type !== TransactionType::Invoice, $method === BooksMethod::Accrual => $this->occurred_on,
            default => $this->invoice_paid ? ($this->invoice_paid_on ?? $this->occurred_on) : null,
        };
    }

    /**
     * The day a sale counts for percentage tax and VAT: billing from April
     * 27, 2024 (RR 3-2024), so an invoice on its date; before then, receipts,
     * so an invoice when it was paid, even after that date (Sec. 7(a)).
     * REVIEW: the transitory rule is written for VAT; percentage tax follows by analogy.
     */
    public function salesTaxDate(): ?CarbonImmutable
    {
        $billing = $this->occurred_on->greaterThanOrEqualTo(CarbonImmutable::parse((string) config('tax.sales_by_invoice_from')));

        return $this->incomeDate($billing ? BooksMethod::Accrual : BooksMethod::Cash);
    }

    /**
     * Sales that count in a year one way or another: dated in it, or paid in it.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forTaxYear(Builder $query, int $year): void
    {
        $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $q) => $q->where('occurred_on', '>=', "{$year}-01-01")->where('occurred_on', '<', ($year + 1).'-01-01'))
            ->orWhere(fn (Builder $q) => $q->where('invoice_paid_on', '>=', "{$year}-01-01")->where('invoice_paid_on', '<', ($year + 1).'-01-01')));
    }

    /**
     * What came in or went out in a month, for the cash books: a paid
     * invoice counts in the month it was paid.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function cashInMonth(Builder $query, int $year, int $month): void
    {
        $start = CarbonImmutable::create($year, $month)->toDateString();
        $end = CarbonImmutable::create($year, $month)->addMonth()->toDateString();

        $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $q) => $q->where('type', '!=', TransactionType::Invoice->value)->where('occurred_on', '>=', $start)->where('occurred_on', '<', $end))
            ->orWhere(fn (Builder $q) => $q->where('type', TransactionType::Invoice->value)->where('invoice_paid', true)->where('invoice_paid_on', '>=', $start)->where('invoice_paid_on', '<', $end)));
    }

    /**
     * The day money moved: a paid invoice's payment date, else the date.
     */
    public function cashDate(): CarbonImmutable
    {
        return $this->type === TransactionType::Invoice && $this->invoice_paid_on !== null ? $this->invoice_paid_on : $this->occurred_on;
    }

    public function certificateStatus(CarbonInterface $today): CertificateStatus
    {
        if ((float) $this->tax_withheld <= 0) {
            return CertificateStatus::NotNeeded;
        }

        if ($this->certificate_received_on !== null) {
            return CertificateStatus::Received;
        }

        $dueBy = $this->occurred_on->copy()
            ->lastOfQuarter()
            ->addDays(config('tax.certificate_grace_days'));

        return $today->greaterThan($dueBy)
            ? CertificateStatus::Missing
            : CertificateStatus::Pending;
    }
}
