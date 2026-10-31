<?php

namespace App\Returns\Forms\Items;

use App\Profiles\Models\TaxProfile;

/**
 * BIR Form 1900 for a permit to use loose-leaf books: the taxpayer, the
 * head office as the one place covered, and the books the app prints.
 *
 * @phpstan-import-type Values from FormItems
 */
class Form1900Items extends ProfileFormItems
{
    /** The books BuwisBuddy prints each month, from the Books screen. */
    public const BOOKS = ['CASH RECEIPTS JOURNAL', 'CASH DISBURSEMENTS JOURNAL'];

    /**
     * @return Values
     */
    public function looseLeafBooks(TaxProfile $profile): array
    {
        [$tin1, $tin2, $tin3] = $this->tin($profile);
        [$address1, $address2] = $this->address((string) $this->upper($profile->registered_address));

        $values = [
            'tin.1' => $tin1,
            'tin.2' => $tin2,
            'tin.3' => $tin3,
            '2' => $profile->rdo_code,
            '3' => $this->name($profile),
            '4.1' => $address1,
            '4.2' => $address2,
            '4A' => $profile->zip_code,
            '5' => preg_replace('/[^\d+]/', '', (string) $profile->contact_number),
            '6' => $this->upper($profile->email),
            '7.tin' => $profile->tin ? implode('-', [$tin1, $tin2, $tin3]) : null,
            '7.branch' => $profile->tin ? '00000' : null,
            '7.name' => $this->upper($profile->trade_name) ?? $this->name($profile),
            '7.rdo' => $profile->rdo_code,
            '7.address' => $this->upper($profile->registered_address),
            '8.books' => true,
            '10' => $this->printedName($profile),
        ];

        foreach (self::BOOKS as $i => $book) {
            $values['9A.'.($i + 1)] = $book;
        }

        return $values;
    }

    /**
     * An address over the form's two lines, broken between words.
     *
     * @return array{string, string}
     */
    private function address(string $address): array
    {
        $width = 70;

        if (mb_strlen($address) <= $width) {
            return [$address, ''];
        }

        $first = trim(mb_substr($address, 0, (int) mb_strrpos(mb_substr($address, 0, $width + 1), ' ')) ?: mb_substr($address, 0, $width));

        return [$first, trim(mb_substr($address, mb_strlen($first)))];
    }
}
