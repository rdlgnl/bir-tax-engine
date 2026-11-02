<?php

namespace App\Returns\Forms\Items;

use App\Loans\Models\Loan;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\DocumentaryStampTax;
use App\Tax\Services\Penalties;

/**
 * BIR Form 2000: documentary stamp tax for the month a loan was signed,
 * with the lender as the other party (the creditor) and the loan on the
 * first line of Schedule 1.
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type Penalty from Penalties
 */
class Form2000Items extends ProfileFormItems
{
    /**
     * @param  Penalty|null  $penalties
     * @return Values
     */
    public function values(TaxProfile $profile, Loan $loan, float $tax, ?array $penalties = null): array
    {
        [$tin1, $tin2, $tin3] = $this->tin($profile);
        $lenderTin = (string) preg_replace('/\D/', '', (string) $loan->lender_tin);
        $surcharge = $penalties['surcharge'] ?? 0.0;
        $interest = $penalties['interest'] ?? 0.0;

        return $this->identity($profile, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '5',
            'name' => '6',
            'address' => ['7.1', 40, '7.2'],
            'zip' => '7A',
            'contact' => '8',
            'email' => '9',
        ]) + [
            '1' => $loan->signed_on->format('mY'),
            '2.no' => true,
            'tin.branch' => '00000',
            '10.creditor' => true,
            '11' => $this->upper($loan->lender),
            '12' => $lenderTin !== '' ? substr($lenderTin, 0, 9).str_pad(substr($lenderTin, 9, 5), 5, '0', STR_PAD_LEFT) : null,
            '14' => $tax,
            '16' => $tax,
            // Penalty lines stay blank on a return filed on time.
            '17A' => $penalties !== null ? $surcharge : null,
            '17B' => $penalties !== null ? $interest : null,
            '17D' => $penalties !== null ? round($surcharge + $interest, 2) : null,
            '18' => round($tax + $surcharge + $interest, 2),
            'p2.tin' => $tin1.$tin2.$tin3 !== '' ? $tin1.$tin2.$tin3.'00000' : null,
            'p2.name' => $this->name($profile),
            's1.1.atc' => (string) config('tax.dst.loan.atc'),
            's1.1.base' => (float) $loan->amount,
            's1.1.rate' => app(DocumentaryStampTax::class)->rateLabel($loan->signed_on),
            's1.1.due' => $tax,
            's1.total' => $tax,
        ];
    }
}
