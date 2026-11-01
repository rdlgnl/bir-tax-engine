<?php

namespace App\Payroll\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Someone the taxpayer employs, paid the same taxable amount each month.
 *
 * @property int $id
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $tin
 * @property string|null $position
 * @property string $monthly_pay
 * @property CarbonImmutable $started_on
 * @property CarbonImmutable|null $ended_on
 */
#[Fillable([
    'last_name',
    'first_name',
    'middle_name',
    'tin',
    'position',
    'monthly_pay',
    'started_on',
    'ended_on',
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
            'started_on' => 'immutable_date',
            'ended_on' => 'immutable_date',
        ];
    }

    public function name(): string
    {
        return Str::squish("{$this->first_name} {$this->last_name}");
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
