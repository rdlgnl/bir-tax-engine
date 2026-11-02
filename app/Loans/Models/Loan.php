<?php

namespace App\Loans\Models;

use App\Data\Concerns\RecordsChanges;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A loan the taxpayer signed. It isn't income; it owes documentary stamp
 * tax, which a bank usually files and deducts itself.
 *
 * @property int $id
 * @property CarbonImmutable $signed_on
 * @property string $amount
 * @property string $lender
 * @property string|null $lender_tin
 * @property 'bank'|'person' $lender_kind
 * @property int $term_months
 * @property CarbonImmutable|null $filed_on
 * @property string|null $reference_number
 * @property string|null $amount_paid
 */
#[Fillable([
    'signed_on',
    'amount',
    'lender',
    'lender_tin',
    'lender_kind',
    'term_months',
])]
class Loan extends Model
{
    use RecordsChanges;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signed_on' => 'immutable_date',
            'amount' => 'decimal:2',
            'term_months' => 'integer',
            'filed_on' => 'immutable_date',
            'amount_paid' => 'decimal:2',
        ];
    }

    /**
     * A bank files the DST itself and deducts it from what it lends.
     */
    public function filedByLender(): bool
    {
        return $this->lender_kind === 'bank';
    }

    public function historyLabel(): string
    {
        return "Loan from {$this->lender}";
    }
}
