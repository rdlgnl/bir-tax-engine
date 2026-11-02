<?php

namespace App\Withholding\Models;

use App\Data\Concerns\RecordsChanges;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A 2307 the taxpayer gave someone they withheld tax from.
 *
 * @property int $id
 * @property int $year
 * @property int $quarter
 * @property string $payee
 * @property CarbonImmutable $given_on
 */
#[Fillable(['year', 'quarter', 'payee', 'given_on'])]
class IssuedCertificate extends Model
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
            'year' => 'integer',
            'quarter' => 'integer',
            'given_on' => 'date',
        ];
    }
}
