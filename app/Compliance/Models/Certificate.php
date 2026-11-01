<?php

namespace App\Compliance\Models;

use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A client's Form 2307: proof they paid tax on the taxpayer's behalf for
 * one quarter. It confirms the credit on every payment it covers.
 *
 * @property int $id
 * @property string $payor_name
 * @property string|null $payor_tin
 * @property int $year
 * @property int $quarter
 * @property string $amount_paid
 * @property string $tax_withheld
 * @property string|null $atc_code
 * @property string|null $file_path
 * @property string|null $original_filename
 * @property bool $linked_by_hand
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'payor_name',
    'payor_tin',
    'year',
    'quarter',
    'amount_paid',
    'tax_withheld',
    'atc_code',
])]
class Certificate extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'quarter' => 'integer',
            'amount_paid' => 'decimal:2',
            'tax_withheld' => 'decimal:2',
            'linked_by_hand' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
