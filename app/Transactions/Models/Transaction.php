<?php

namespace App\Transactions\Models;

use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Something that happened to the business: money in, an invoice, or an expense.
 *
 * @property int $id
 * @property TransactionType $type
 * @property CarbonImmutable $occurred_on
 * @property string $counterparty
 * @property string $amount
 * @property string $tax_withheld
 * @property CarbonImmutable|null $certificate_received_on
 * @property string|null $invoice_number
 * @property bool $invoice_paid
 * @property ExpenseCategory|null $category
 * @property bool $has_valid_invoice
 * @property string|null $description
 */
#[Fillable([
    'type',
    'occurred_on',
    'counterparty',
    'amount',
    'tax_withheld',
    'certificate_received_on',
    'invoice_number',
    'invoice_paid',
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
        'invoice_paid' => false,
        'has_valid_invoice' => false,
    ];

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
            'certificate_received_on' => 'date',
            'invoice_paid' => 'boolean',
            'category' => ExpenseCategory::class,
            'has_valid_invoice' => 'boolean',
        ];
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

    public function quarter(): int
    {
        return $this->occurred_on->quarter;
    }

    public function isIncome(): bool
    {
        return $this->type->isIncome();
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
