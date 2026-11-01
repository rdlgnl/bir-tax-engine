<?php

namespace App\Profiles\Models;

use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The choices one tax year was computed on. The 8% option or graduated
 * rates is elected on the year's first quarterly return and holds for the
 * year; registrations can change, so each year keeps what it was taxed
 * on. A blank field means the profile's current value.
 *
 * @property int $id
 * @property int $year
 * @property Regime|null $regime
 * @property DeductionMethod|null $deduction_method
 * @property bool|null $vat_registered
 * @property bool|null $registered_percentage_tax
 */
#[Fillable(['year', 'regime', 'deduction_method', 'vat_registered', 'registered_percentage_tax'])]
class TaxYear extends Model
{
    /** The profile's fields a year keeps its own value of. */
    public const ELECTIONS = ['regime', 'deduction_method', 'vat_registered', 'registered_percentage_tax'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'regime' => Regime::class,
            'deduction_method' => DeductionMethod::class,
            'vat_registered' => 'boolean',
            'registered_percentage_tax' => 'boolean',
        ];
    }
}
