<?php

namespace App\Notices\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Something that happened, shown under Notifications.
 *
 * @property int $id
 * @property string $kind
 * @property string $title
 * @property string|null $detail
 * @property string|null $url
 * @property CarbonImmutable $created_at
 */
#[Fillable(['kind', 'title', 'detail', 'url'])]
class Notice extends Model {}
