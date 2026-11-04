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
    // for (a person or a company); professional fees depend on the payee's
    // sworn declaration of gross income: ₱3,000,000 for a person, ₱720,000
    // for a company (RR 11-2018; the 1601-EQ's ATC schedule). The codes and
    // rates match the 1601-EQ (January 2019) and the 2307 (January 2018).
    // REVIEW: whether a company without a declaration is withheld at 15%.
    'withholding' => [
        'atcs' => [
            // 5% only for a payee who isn't VAT-registered (RR 14-2018).
            'WI010' => ['kind' => 'professional', 'payee' => 'person', 'declaration' => true, 'rate' => 0.05, 'label' => 'Professional fee, with sworn declaration, payee not VAT-registered'],
            'WI011' => ['kind' => 'professional', 'payee' => 'person', 'declaration' => false, 'rate' => 0.10, 'label' => 'Professional fee, without sworn declaration or VAT-registered'],
            'WC010' => ['kind' => 'professional', 'payee' => 'company', 'declaration' => true, 'rate' => 0.10, 'label' => 'Professional fee to a company, gross income ₱720,000 or less'],
            'WC011' => ['kind' => 'professional', 'payee' => 'company', 'declaration' => false, 'rate' => 0.15, 'label' => 'Professional fee to a company, gross income over ₱720,000'],
            'WI100' => ['kind' => 'rent', 'payee' => 'person', 'declaration' => null, 'rate' => 0.05, 'label' => 'Rent of real property'],
            'WC100' => ['kind' => 'rent', 'payee' => 'company', 'declaration' => null, 'rate' => 0.05, 'label' => 'Rent of real property'],
            'WI120' => ['kind' => 'contractor', 'payee' => 'person', 'declaration' => null, 'rate' => 0.02, 'label' => 'Contractor or services'],
            'WC120' => ['kind' => 'contractor', 'payee' => 'company', 'declaration' => null, 'rate' => 0.02, 'label' => 'Contractor or services'],
        ],
        // 0619-E: day of the next month; 1601-EQ: last day of the month after the quarter.
        'monthly_due_day' => 10,
        // 1601-C for December: January 15 (RR 2-98 Sec. 2.83 as amended).
        'december_compensation_due_day' => 15,
        // eFPS filers e-file on other days: the 0619-E by the 15th of the next
        // month (RR 11-2018), and the 1601-C, December's included, by their
        // group's day of the next month (RR 26-2002): Group A the 15th down to
        // Group E the 11th. These are the e-filing dates; payment is by the
        // 15th, December's by January 20 (PwC 2026 tax calendar).
        // REVIEW: the payment dates, which the app doesn't show apart.
        'efps' => [
            'monthly_due_day' => 15,
            'compensation_due_day' => ['A' => 15, 'B' => 14, 'C' => 13, 'D' => 12, 'E' => 11],
        ],
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

    // Alphanumeric tax codes (ATC) printed on the returns. The income tax
    // codes match the 1701Q (January 2018) item 8, and PT010 the 2551Q's
    // table ("persons exempt from VAT under Sec. 109(BB)").
    'atc' => [
        'income_tax' => [
            'eight_percent' => ['business' => 'II015', 'profession' => 'II017', 'mixed' => 'II016'],
            'graduated' => ['business' => 'II012', 'profession' => 'II014', 'mixed' => 'II013'],
        ],
        'percentage_tax' => 'PT010',
        // The 2550Q (April 2024) has no ATC box; this is shown in the app only.
        // REVIEW: the code, if the BIR asks for one.
        'vat' => 'VT010',
    ],

];
