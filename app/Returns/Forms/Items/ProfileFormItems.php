<?php

namespace App\Returns\Forms\Items;

use App\Profiles\Models\TaxProfile;
use Illuminate\Support\Str;

/**
 * Registration forms (1900, 1905) are filled from the tax profile alone,
 * not from a return. The taxpayer prints the way the returns print them.
 */
abstract class ProfileFormItems
{
    /**
     * The TIN in its three groups of three digits; the branch code stays blank.
     *
     * @return array{string, string, string}
     */
    protected function tin(TaxProfile $profile): array
    {
        $digits = substr(preg_replace('/\D/', '', (string) $profile->tin), 0, 9);

        return [substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 3)];
    }

    /**
     * "LAST NAME, FIRST NAME MIDDLE NAME", as the forms ask.
     */
    protected function name(TaxProfile $profile): string
    {
        $name = $profile->nameParts();

        return $name['last'] === null
            ? Str::upper(trim((string) $profile->full_name))
            : Str::upper(Str::squish("{$name['last']}, {$name['first']} {$name['middle']}"));
    }

    /**
     * The name for the signature line: "FIRST MIDDLE LAST".
     */
    protected function printedName(TaxProfile $profile): string
    {
        $name = $profile->nameParts();

        return $name['last'] === null
            ? Str::upper(trim((string) $profile->full_name))
            : Str::upper(Str::squish("{$name['first']} {$name['middle']} {$name['last']}"));
    }

    protected function upper(?string $value): ?string
    {
        return $value === null ? null : Str::upper(trim($value));
    }
}
