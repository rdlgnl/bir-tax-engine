<?php

namespace App\Payroll\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A 2316 given to an employee for a year.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $year
 * @property CarbonImmutable $given_on
 */
#[Fillable(['employee_id', 'year', 'given_on'])]
class EmployeeCertificate extends Model
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
            'given_on' => 'immutable_date',
        ];
    }
}
