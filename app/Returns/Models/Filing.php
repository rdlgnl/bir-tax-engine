<?php

namespace App\Returns\Models;

use App\Returns\Services\ReturnForm;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\FilingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A return the taxpayer told us they filed, and what they paid with it.
 * Filing quarter 1 locks the year's tax method; a filed quarter can only
 * change through an amendment.
 *
 * @property int $id
 * @property int $year
 * @property int $quarter
 * @property int $month
 * @property string $form
 * @property int $amendment 0 for the original return, then 1, 2… for each amended return
 * @property CarbonImmutable $filed_on
 * @property string|null $reference_number
 * @property string|null $amount_paid
 * @property CarbonImmutable|null $paid_on
 * @property string|null $payment_channel
 * @property Snapshot|LegacySnapshot|null $snapshot
 *
 * @phpstan-import-type Snapshot from ReturnForm
 * @phpstan-import-type LegacySnapshot from ReturnForm
 */
#[Fillable([
    'year',
    'quarter',
    'month',
    'form',
    'amendment',
    'filed_on',
    'reference_number',
    'amount_paid',
    'paid_on',
    'payment_channel',
    'snapshot',
])]
#[UseFactory(FilingFactory::class)]
class Filing extends Model
{
    /** @use HasFactory<FilingFactory> */
    use HasFactory;

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
            'month' => 'integer',
            'amendment' => 'integer',
            'filed_on' => 'date',
            'amount_paid' => 'decimal:2',
            'paid_on' => 'date',
            'snapshot' => 'array',
        ];
    }

    public function isPaid(): bool
    {
        return $this->paid_on !== null;
    }

    /**
     * What was paid toward the tax itself: the payment less any surcharge
     * and interest it included, which later returns don't credit.
     */
    public function taxPaid(): float
    {
        $penalties = is_array($this->snapshot) ? (float) ($this->snapshot['penalties']['total'] ?? 0) : 0.0;

        return max(0.0, round((float) $this->amount_paid - $penalties, 2));
    }

    /**
     * The Q1 income tax return, which locks the year's tax method.
     */
    public static function firstQuarterReturn(int $year): ?self
    {
        return static::query()->where('year', $year)->where('quarter', 1)->where('form', '1701Q')->where('amendment', 0)->first();
    }

    /**
     * The income tax return covering a date, if it has been filed.
     */
    public static function incomeTaxReturnCovering(CarbonInterface $date): ?self
    {
        return static::query()
            ->where('year', $date->year)
            ->where('quarter', $date->quarter)
            ->whereIn('form', ReturnForm::INCOME_TAX_FORMS)
            ->where('amendment', 0)
            ->first();
    }
}
