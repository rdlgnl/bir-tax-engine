<?php

namespace App\Data\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * One change to the taxpayer's records: a record added, edited or
 * removed, with its fields before and after. Appended, never edited or
 * removed; only "Erase all data" clears them, and a restore brings back
 * the backup's own.
 *
 * @property int $id
 * @property string $subject what kind of record: transaction, filing, profile…
 * @property int|null $subject_id
 * @property 'created'|'updated'|'deleted'|'restored' $event
 * @property string $label the record in a few words, as it stood
 * @property array<string, mixed>|null $before the record as it was (all of it, for an edit too)
 * @property array<string, mixed>|null $after the record as added, or the fields an edit changed
 * @property string|null $cause the screen or action it came from
 * @property CarbonImmutable $at
 */
#[Fillable(['subject', 'subject_id', 'event', 'label', 'before', 'after', 'cause', 'at'])]
class RecordChange extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('The history of changes is only ever added to.'));
        static::deleting(fn () => throw new LogicException('The history of changes is only ever added to.'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'at' => 'immutable_datetime',
        ];
    }
}
