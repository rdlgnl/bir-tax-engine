<?php

namespace App\Compliance\Rules;

use App\Returns\Models\Filing;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

/**
 * Entries can't land in a year whose annual return is filed: changing it
 * needs an amended annual return. A filed quarter can still change; its
 * 1701Q is then amended.
 */
class NotInClosedYear implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $date = CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            return; // The date rule reports this.
        }

        if (self::closes($date)) {
            $fail(self::message($date));
        }
    }

    public static function closes(CarbonInterface $date): bool
    {
        return Filing::incomeTaxReturnCovering(CarbonImmutable::create($date->year, 12, 31)) !== null;
    }

    public static function message(CarbonInterface $date): string
    {
        return "Your {$date->year} annual return is already filed. Changing that year needs an amended annual return, which isn't available yet.";
    }
}
