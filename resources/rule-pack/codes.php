<?php

/*
|--------------------------------------------------------------------------
| Rule pack · Tax codes
|--------------------------------------------------------------------------
|
| ATC codes, withholding rates by payee, and their due days and data file names.
|
| Part of config/tax.php, reviewed and versioned on its own: its version
| and sign-off are under `parts.codes` there. Amounts are in pesos.
|
*/

return [

    // Withholding as an agent: the tax a taxpayer keeps from what they pay
    // others and remits on 0619-E (monthly) and 1601-EQ (quarterly), with a
    // 2307 for each payee. Each ATC: what it covers, the rate, and who it is
    // for (a person or a company); professional fees to a person depend on
    // their sworn declaration (RR 11-2018).
    // REVIEW: check the codes and rates against the current RR before relying on them.
    'withholding' => [
        'atcs' => [
            // 5% only for a payee who isn't VAT-registered (RR 14-2018).
            'WI010' => ['kind' => 'professional', 'payee' => 'person', 'declaration' => true, 'rate' => 0.05, 'label' => 'Professional fee, with sworn declaration, payee not VAT-registered'],
            'WI011' => ['kind' => 'professional', 'payee' => 'person', 'declaration' => false, 'rate' => 0.10, 'label' => 'Professional fee, without sworn declaration or VAT-registered'],
            'WC010' => ['kind' => 'professional', 'payee' => 'company', 'declaration' => null, 'rate' => 0.10, 'label' => 'Professional fee to a company'],
            'WI100' => ['kind' => 'rent', 'payee' => 'person', 'declaration' => null, 'rate' => 0.05, 'label' => 'Rent of real property'],
            'WC100' => ['kind' => 'rent', 'payee' => 'company', 'declaration' => null, 'rate' => 0.05, 'label' => 'Rent of real property'],
            'WI120' => ['kind' => 'contractor', 'payee' => 'person', 'declaration' => null, 'rate' => 0.02, 'label' => 'Contractor or services'],
            'WC120' => ['kind' => 'contractor', 'payee' => 'company', 'declaration' => null, 'rate' => 0.02, 'label' => 'Contractor or services'],
        ],
        // 0619-E: day of the next month; 1601-EQ: last day of the month after the quarter.
        'monthly_due_day' => 10,
        // 1601-C for December: January 15 (RR 2-98 Sec. 2.83 as amended).
        'december_compensation_due_day' => 15,
        // 13th month pay and other benefits left untaxed, together, each year
        // (NIRC Sec. 32(B)(7)(e); RR 11-2018 Sec. 2.78.1(B)(11)).
        'benefits_exempt' => 90_000,
        // Pay for the year at or below this isn't withheld on (RR 11-2018 Sec. 2.78.1(B)).
        'compensation_exempt' => 250_000,
        // The employer's copies of the 2316s to the BIR, scanned, with the
        // substituted-filing list (RR 16-2021): month and day of the next year.
        'certificates_to_bir' => [2, 28],
        // The quarterly list of payees (QAP) filed with the 1601-EQ, in the
        // alphalist layout: header, a line per payee, control line.
        // REVIEW: check the layout and file name against the current alphalist module.
        'qap_file_name' => '{tin}{branch}{period}1601EQ.DAT',
        // The annual alphalist filed with the 1604-E, in the same layout, for the year to December 31.
        // REVIEW: check the layout and file name against the current alphalist module.
        'alphalist_file_name' => '{tin}{branch}1231{year}1604E.DAT',
    ],

    // Alphanumeric tax codes (ATC) printed on the returns.
    // REVIEW: check against the current 1701Q and 2551Q before relying on them.
    'atc' => [
        'income_tax' => [
            'eight_percent' => ['business' => 'II015', 'profession' => 'II017', 'mixed' => 'II016'],
            'graduated' => ['business' => 'II012', 'profession' => 'II014', 'mixed' => 'II013'],
        ],
        'percentage_tax' => 'PT010',
        // REVIEW: the 2550Q's ATC for VAT on sale of services/goods.
        'vat' => 'VT010',
    ],

];
