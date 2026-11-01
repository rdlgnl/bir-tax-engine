<?php

namespace App\Letters\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A letter from the BIR, as the taxpayer described it, with its photo.
 *
 * @property int $id
 * @property string $kind one of config('letters.kinds')
 * @property CarbonImmutable|null $dated_on
 * @property string|null $office
 * @property string|null $form
 * @property int|null $year
 * @property int|null $quarter
 * @property int|null $month
 * @property CarbonImmutable|null $reply_by
 * @property string|null $notes
 * @property string|null $file_path
 * @property string|null $original_filename
 * @property CarbonImmutable|null $resolved_on
 */
#[Fillable(['kind', 'dated_on', 'office', 'form', 'year', 'quarter', 'month', 'reply_by', 'notes'])]
class Letter extends Model
{
    protected $table = 'bir_letters';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dated_on' => 'immutable_date',
            'year' => 'integer',
            'quarter' => 'integer',
            'month' => 'integer',
            'reply_by' => 'immutable_date',
            'resolved_on' => 'immutable_date',
        ];
    }

    public function kindLabel(): string
    {
        return (string) config("letters.kinds.{$this->kind}.label", 'A letter from the BIR');
    }

    /**
     * Letters that start an audit or assessment need a professional.
     */
    public function needsHelp(): bool
    {
        return (bool) config("letters.kinds.{$this->kind}.help", false);
    }
}
