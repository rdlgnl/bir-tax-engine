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

    /**
     * Text over two lines of the given width, broken between words.
     *
     * @return array{string, string}
     */
    protected function wrap(string $text, int $width): array
    {
        if (mb_strlen($text) <= $width) {
            return [$text, ''];
        }

        $first = trim(mb_substr($text, 0, (int) mb_strrpos(mb_substr($text, 0, $width + 1), ' ')) ?: mb_substr($text, 0, $width));

        return [$first, trim(mb_substr($text, mb_strlen($first)))];
    }

    /**
     * The taxpayer's identity in the boxes most BIR forms share.
     *
     * @param  array{tin: array{string, string, string}, rdo: string, name: string, address?: array{string, int, string}, zip?: string, contact?: string, email?: string}  $items
     * @return array<string, string|null>
     */
    protected function identity(TaxProfile $profile, array $items): array
    {
        [$tin1, $tin2, $tin3] = $this->tin($profile);
        $values = [
            $items['tin'][0] => $tin1,
            $items['tin'][1] => $tin2,
            $items['tin'][2] => $tin3,
            $items['rdo'] => $profile->rdo_code,
            $items['name'] => $this->name($profile),
        ];

        if (isset($items['address'])) {
            [$first, $width, $second] = $items['address'];
            [$values[$first], $values[$second]] = $this->wrap((string) $this->upper($profile->registered_address), $width);
        }

        if (isset($items['zip'])) {
            $values[$items['zip']] = $profile->zip_code;
        }

        if (isset($items['contact'])) {
            $values[$items['contact']] = preg_replace('/[^\d+]/', '', (string) $profile->contact_number) ?: null;
        }

        if (isset($items['email'])) {
            $values[$items['email']] = $this->upper($profile->email);
        }

        return $values;
    }

    protected function upper(?string $value): ?string
    {
        return $value === null ? null : Str::upper(trim($value));
    }
}
