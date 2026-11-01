<?php

namespace App\Corporations\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One quarter of a corporation's books: that quarter's figures alone, not
 * the year to date, and its 1702Q once filed. The 4th quarter has no 1702Q:
 * its row records the year's 1702-RT.
 *
 * @property int $id
 * @property int $corporation_id
 * @property int $year
 * @property int $quarter
 * @property string $sales
 * @property string $cost_of_sales
 * @property string $other_income
 * @property string $deductions
 * @property string $withheld
 * @property CarbonImmutable|null $filed_on
 * @property string|null $reference_number
 * @property string|null $amount_paid
 */
#[Fillable([
    'year',
    'quarter',
    'sales',
    'cost_of_sales',
    'other_income',
    'deductions',
    'withheld',
])]
class CorporationQuarter extends Model
{
    /**
     * @return BelongsTo<Corporation, $this>
     */
    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

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
            'sales' => 'decimal:2',
            'cost_of_sales' => 'decimal:2',
            'other_income' => 'decimal:2',
            'deductions' => 'decimal:2',
            'withheld' => 'decimal:2',
            'filed_on' => 'immutable_date',
            'amount_paid' => 'decimal:2',
        ];
    }
}
