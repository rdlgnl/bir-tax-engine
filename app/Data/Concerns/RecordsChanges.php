<?php

namespace App\Data\Concerns;

use App\Data\Models\RecordChange;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Writes each change to the model into the history of changes: the whole
 * record when it's added or removed; when it's edited, the record as it
 * was and the fields that changed. Bulk updates through the query builder aren't models and aren't
 * recorded.
 *
 * @mixin Model
 */
trait RecordsChanges
{
    /**
     * Bookkeeping columns left out of the history.
     *
     * @var list<string>
     */
    private static array $unrecorded = ['created_at', 'updated_at'];

    public static function bootRecordsChanges(): void
    {
        static::created(fn (Model $model) => self::recordChange($model, 'created', null, self::recordable($model->getAttributes())));

        static::updated(function (Model $model) {
            $after = self::recordable($model->getChanges());

            if ($after !== []) {
                self::recordChange($model, 'updated', self::recordable($model->getRawOriginal()), $after);
            }
        });

        static::deleted(fn (Model $model) => self::recordChange($model, 'deleted', self::recordable($model->getRawOriginal()), null));
    }

    /**
     * The record in a few words, for the history.
     */
    public function historyLabel(): string
    {
        return Str::headline(class_basename($this)).' '.$this->getKey();
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private static function recordChange(Model $model, string $event, ?array $before, ?array $after): void
    {
        RecordChange::query()->create([
            'subject' => Str::snake(class_basename($model)),
            'subject_id' => $model->getKey(),
            'event' => $event,
            'label' => method_exists($model, 'historyLabel') ? $model->historyLabel() : class_basename($model),
            'before' => $before,
            'after' => $after,
            'cause' => request()->route()?->getName() ?? (app()->runningInConsole() ? 'console' : null),
            'at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function recordable(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(self::$unrecorded));
    }
}
