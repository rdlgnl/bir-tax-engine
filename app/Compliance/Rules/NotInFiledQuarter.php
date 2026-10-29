<?php

namespace App\Compliance\Rules;

use App\Compliance\Models\Filing;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * A filed quarter only changes through an amended return, so new or edited
 * entries can't land in one.
 */
class NotInFiledQuarter implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $date = CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            return; // The date rule reports this.
        }

        if (Filing::incomeTaxReturnCovering($date) !== null) {
            $fail(self::message($date));
        }
    }

    public static function message(CarbonImmutable $date): string
    {
        return "Q{$date->quarter} {$date->year} is already filed. Changing it needs an amended return, which isn't available yet.";
    }
}
