<?php

namespace App\Books\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A month the taxpayer has copied into their registered books.
 *
 * @property int $id
 * @property int $year
 * @property int $month
 * @property CarbonImmutable $copied_at
 */
#[Fillable([
    'year',
    'month',
    'copied_at',
])]
class BooksMonth extends Model
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
            'month' => 'integer',
            'copied_at' => 'datetime',
        ];
    }
}
