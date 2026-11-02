<?php

namespace App\Compliance\Models;

use App\Data\Concerns\RecordsChanges;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The taxpayer's change to one day of the holiday list: a holiday added or
 * renamed, or a listed day that isn't one that year (a holiday moved by
 * proclamation, say). It wins over the rule pack's list and the estimate.
 *
 * @property int $id
 * @property CarbonImmutable $date
 * @property string|null $name
 * @property bool $holiday
 */
#[Fillable(['date', 'name', 'holiday'])]
class HolidayChange extends Model
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
            'date' => 'immutable_date',
            'holiday' => 'boolean',
        ];
    }

    public function historyLabel(): string
    {
        return "Holiday on {$this->date->format('M j, Y')}";
    }
}
