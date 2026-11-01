<?php

namespace App\Compliance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * An employer's Form 2316 for a year: the salary paid and the tax withheld
 * on it. Its figures replace the estimate on the annual return.
 *
 * @property int $id
 * @property int $year
 * @property string $employer_name
 * @property string|null $employer_tin
 * @property string $gross_compensation
 * @property string $non_taxable_compensation
 * @property string $tax_withheld
 * @property string|null $file_path
 * @property string|null $original_filename
 */
#[Fillable([
    'year',
    'employer_name',
    'employer_tin',
    'gross_compensation',
    'non_taxable_compensation',
    'tax_withheld',
])]
class CompensationCertificate extends Model
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
            'gross_compensation' => 'decimal:2',
            'non_taxable_compensation' => 'decimal:2',
            'tax_withheld' => 'decimal:2',
        ];
    }
}
