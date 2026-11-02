<?php

namespace App\Payroll\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Someone the taxpayer employs, paid the same basic pay each month. Their
 * share of SSS, PhilHealth and Pag-IBIG comes off it, and de minimis
 * benefits are paid on top; neither is taxed. A minimum wage earner's
 * basic pay isn't taxed at all. An earlier employer's pay and withholding
 * count only in the year they started.
 *
 * @property int $id
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $tin
 * @property string|null $position
 * @property string $monthly_pay
 * @property string $contributions
 * @property string $de_minimis
 * @property bool $minimum_wage
 * @property CarbonImmutable $started_on
 * @property CarbonImmutable|null $ended_on
 * @property string|null $previous_pay
 * @property string|null $previous_withheld
 */
#[Fillable([
    'last_name',
    'first_name',
    'middle_name',
    'tin',
    'position',
    'monthly_pay',
    'contributions',
    'de_minimis',
    'minimum_wage',
    'started_on',
    'ended_on',
    'previous_pay',
    'previous_withheld',
])]
class Employee extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_pay' => 'decimal:2',
            'contributions' => 'decimal:2',
            'de_minimis' => 'decimal:2',
            'minimum_wage' => 'boolean',
            'started_on' => 'immutable_date',
            'ended_on' => 'immutable_date',
            'previous_pay' => 'decimal:2',
            'previous_withheld' => 'decimal:2',
        ];
    }

    public function name(): string
    {
        return Str::squish("{$this->first_name} {$this->last_name}");
    }

    /**
     * Pay and tax withheld by an earlier employer in the year, which only
     * the year they started here can have.
     *
     * @return array{pay: float, withheld: float}
     */
    public function previousEmployer(int $year): array
    {
        return $this->started_on->year === $year
            ? ['pay' => (float) $this->previous_pay, 'withheld' => (float) $this->previous_withheld]
            : ['pay' => 0.0, 'withheld' => 0.0];
    }

    /**
     * Whether they worked any of the month.
     */
    public function workedIn(int $year, int $month): bool
    {
        $start = CarbonImmutable::create($year, $month);

        return $this->started_on->lessThanOrEqualTo($start->endOfMonth())
            && ($this->ended_on === null || $this->ended_on->greaterThanOrEqualTo($start));
    }
}
