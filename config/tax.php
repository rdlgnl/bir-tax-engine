<?php

/*
|--------------------------------------------------------------------------
| BIR rule pack
|--------------------------------------------------------------------------
|
| Every rate, threshold, deadline and citation the tax engine uses. Nothing
| in app/Tax hard-codes a number: change it here and the whole app follows.
|
| REVIEW STATUS: encoded from the NIRC as amended by RA 10963 (TRAIN) and
| the issuances cited below, and cross-checked against the figures on the
| approved screens. The design's "blueprint doc" was not available, so
| `reviewed` stays false until a tax professional signs off.
|
| Amounts are in pesos.
|
*/

return [

    'version' => '2026.10',

    'reviewed' => false,

    // What each rule pack changed, newest first, for the "what changed" screen.
    // Each: area (Rules, Deadline calendar, Forms), title, summary.
    'changes' => [
        '2026.10' => [
            ['area' => 'Rules', 'title' => 'First rule pack', 'summary' => '8% option, graduated rates (2023 table), the 40% OSD, 3% percentage tax and the ₱3,000,000 limits.'],
            ['area' => 'Deadline calendar', 'title' => 'Deadlines and holidays through 2027', 'summary' => 'Due dates that fall on weekends or holidays move to the next working day.'],
            ['area' => 'Forms', 'title' => 'BIR forms', 'summary' => '1701Q, 1701-MS, 1701A, 1701 and 2551Q, with 1905 and 1900 for registration.'],
        ],
    ],

    'eight_percent' => [
        'rate' => 0.08,
        // Only purely self-employed individuals get this reduction.
        'exempt_amount' => 250_000,
        // Above this the 8% option ends for the whole year (VAT threshold).
        'gross_limit' => 3_000_000,
    ],

    // Annual table for 2023 onwards: [over, base tax, rate on the excess].
    'graduated_brackets' => [
        [0, 0, 0.00],
        [250_000, 0, 0.15],
        [400_000, 22_500, 0.20],
        [800_000, 102_500, 0.25],
        [2_000_000, 402_500, 0.30],
        [8_000_000, 2_202_500, 0.35],
    ],

    'osd_rate' => 0.40,

    'percentage_tax_rate' => 0.03,

    // Withholding as an agent: the tax a taxpayer keeps from what they pay
    // others and remits on 0619-E (monthly) and 1601-EQ (quarterly), with a
    // 2307 for each payee. Each ATC: what it covers, the rate, and who it is
    // for (a person or a company); professional fees to a person depend on
    // their sworn declaration (RR 11-2018).
    // REVIEW: check the codes and rates against the current RR before relying on them.
    'withholding' => [
        'atcs' => [
            'WI010' => ['kind' => 'professional', 'payee' => 'person', 'declaration' => true, 'rate' => 0.05, 'label' => 'Professional fee, with sworn declaration'],
            'WI011' => ['kind' => 'professional', 'payee' => 'person', 'declaration' => false, 'rate' => 0.10, 'label' => 'Professional fee, without sworn declaration'],
            'WC010' => ['kind' => 'professional', 'payee' => 'company', 'declaration' => null, 'rate' => 0.10, 'label' => 'Professional fee to a company'],
            'WI100' => ['kind' => 'rent', 'payee' => 'person', 'declaration' => null, 'rate' => 0.05, 'label' => 'Rent of real property'],
            'WC100' => ['kind' => 'rent', 'payee' => 'company', 'declaration' => null, 'rate' => 0.05, 'label' => 'Rent of real property'],
            'WI120' => ['kind' => 'contractor', 'payee' => 'person', 'declaration' => null, 'rate' => 0.02, 'label' => 'Contractor or services'],
            'WC120' => ['kind' => 'contractor', 'payee' => 'company', 'declaration' => null, 'rate' => 0.02, 'label' => 'Contractor or services'],
        ],
        // 0619-E: day of the next month; 1601-EQ: last day of the month after the quarter.
        'monthly_due_day' => 10,
        // The quarterly list of payees (QAP) filed with the 1601-EQ, in the
        // alphalist layout: header, a line per payee, control line.
        // REVIEW: check the layout and file name against the current alphalist module.
        'qap_file_name' => '{tin}{branch}{period}1601EQ.DAT',
        // The annual alphalist filed with the 1604-E, in the same layout, for the year to December 31.
        // REVIEW: check the layout and file name against the current alphalist module.
        'alphalist_file_name' => '{tin}{branch}1231{year}1604E.DAT',
    ],

    // Value-added tax, for VAT-registered taxpayers. Amounts are kept net of
    // VAT; only purchase invoices in the taxpayer's name can be claimed.
    'vat' => [
        'rate' => 0.12,
    ],

    'micro_taxpayer_limit' => 3_000_000,

    // Summary Alphalist of Withholding Taxes, the file that goes with an
    // income tax return claiming 2307 credits, in the layout of the BIR's
    // alphalist data entry tool: one header, one line per payor, one control.
    // REVIEW: check the layout and file name against the current alphalist
    // data entry module before relying on them.
    'sawt' => [
        'branch_code' => '0000',
        // {tin} nine digits, {branch} four, {period} MMYYYY of the quarter's last month.
        'file_name' => '{tin}{branch}{period}{form}.DAT',
    ],

    // Summary List of Sales and Purchases, filed with each 2550Q, as the BIR's
    // RELIEF data entry module writes it: a header line, then one line per
    // customer or supplier. Purchases are counted as services, except
    // equipment, as goods.
    // REVIEW: check the layout and file names against the current RELIEF
    // module before relying on them.
    'slsp' => [
        // {tin} nine digits, {kind} S or P, {period} MMYYYY of the quarter's last month.
        'file_name' => '{tin}{kind}{period}.DAT',
        'fiscal_month_end' => 12,
    ],

    // Books of accounts (NIRC Sec. 232 and 235, as amended by EOPT).
    // REVIEW: confirm the sales level that requires computerized books
    // registration before relying on it.
    'books' => [
        'retention_years' => 5,
        'computerized_limit' => 3_000_000,
        // Loose-leaf books are bound within this many days after year end (BIR Form 1900).
        'bind_within_days' => 15,
    ],

    // Rates clients are expected to withhold, shown as hints only.
    'expected_withholding' => [
        'services' => 0.05,
        'services_without_declaration' => 0.10,
        'products' => 0.01,
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

    // The BIR's own forms the downloads are filled on. `template` names the
    // PDF and its field map in resources/forms/bir; a new BIR edition is a new
    // template (scripts/bir-forms/fetch.sh) and a new map. The income tax
    // forms say "DO NOT enter Centavos", so their amounts are whole pesos.
    'forms' => [
        '1701Q' => ['edition' => 'January 2018 (ENCS)', 'template' => '1701Q-2018-01', 'whole_pesos' => true],
        '1701-MS' => ['edition' => 'August 2024 (ENCS)', 'template' => '1701-MS-2024-08', 'whole_pesos' => true],
        '1701A' => ['edition' => 'January 2018 (ENCS)', 'template' => '1701A-2018-01', 'whole_pesos' => true],
        '1701' => ['edition' => 'January 2018 (ENCS)', 'template' => '1701-2018-01', 'whole_pesos' => true],
        '2551Q' => ['edition' => 'January 2018 (ENCS)', 'template' => '2551Q-2018-01', 'whole_pesos' => false],
        '2550Q' => ['edition' => 'April 2024 (ENCS)', 'template' => '2550Q-2024-04', 'whole_pesos' => false],
        // Withholding agent: monthly and quarterly remittance, the payee's certificate.
        '0619-E' => ['edition' => 'January 2018', 'template' => '0619-E-2018-01', 'whole_pesos' => false],
        '1601-EQ' => ['edition' => 'January 2019 (ENCS)', 'template' => '1601-EQ-2019-01', 'whole_pesos' => false],
        '1604-E' => ['edition' => 'January 2018 (ENCS)', 'template' => '1604-E-2018-01', 'whole_pesos' => false],
        '2307' => ['edition' => 'January 2018 (ENCS)', 'template' => '2307-2018-01', 'whole_pesos' => false],
        // Registration forms: updating the COR, and the loose-leaf books permit.
        '1905' => ['edition' => 'October 2025 (ENCS)', 'template' => '1905-2025-10', 'whole_pesos' => true],
        '1900' => ['edition' => 'October 2025 (ENCS)', 'template' => '1900-2025-10', 'whole_pesos' => true],
    ],

    // A 2307 is due from the client within this many days after quarter end.
    'certificate_grace_days' => 20,

    'deadlines' => [
        // Quarterly income tax (1701Q): month and day, same year.
        'income_tax_quarterly' => [1 => [5, 15], 2 => [8, 15], 3 => [11, 15]],
        // Annual income tax (1701A / 1701): month and day of the next year.
        'income_tax_annual' => [4, 15],
        // Percentage tax (2551Q): days after quarter end.
        'percentage_tax_days_after_quarter' => 25,
        // Quarterly VAT (2550Q) and its SLSP: days after the quarter ends.
        'vat_days_after_quarter' => 25,
        // Sworn declaration to clients: month and day of each year.
        'sworn_declaration' => [1, 15],
    ],

    // Non-working days that push a deadline to the next working day, with
    // the names the calendar shows. Regular holidays plus the usual special
    // non-working days. Check each year's proclamation before relying on these.
    'holidays' => [
        '2026-01-01' => "New Year's Day",
        '2026-02-17' => 'Chinese New Year',
        '2026-04-02' => 'Maundy Thursday',
        '2026-04-03' => 'Good Friday',
        '2026-04-04' => 'Black Saturday',
        '2026-04-09' => 'Araw ng Kagitingan',
        '2026-05-01' => 'Labor Day',
        '2026-06-12' => 'Independence Day',
        '2026-08-21' => 'Ninoy Aquino Day',
        '2026-08-31' => 'National Heroes Day',
        '2026-11-01' => "All Saints' Day",
        '2026-11-30' => 'Bonifacio Day',
        '2026-12-08' => 'Immaculate Conception',
        '2026-12-24' => 'Christmas Eve',
        '2026-12-25' => 'Christmas Day',
        '2026-12-30' => 'Rizal Day',
        '2026-12-31' => 'Last day of the year',
        '2027-01-01' => "New Year's Day",
        '2027-03-25' => 'Maundy Thursday',
        '2027-03-26' => 'Good Friday',
        '2027-03-27' => 'Black Saturday',
        '2027-04-09' => 'Araw ng Kagitingan',
        '2027-05-01' => 'Labor Day',
        '2027-06-12' => 'Independence Day',
        '2027-08-21' => 'Ninoy Aquino Day',
        '2027-08-30' => 'National Heroes Day',
        '2027-11-01' => "All Saints' Day",
        '2027-11-30' => 'Bonifacio Day',
        '2027-12-08' => 'Immaculate Conception',
        '2027-12-24' => 'Christmas Eve',
        '2027-12-25' => 'Christmas Day',
        '2027-12-30' => 'Rizal Day',
        '2027-12-31' => 'Last day of the year',
    ],

    'rules' => [
        'VAT-12PCT' => [
            'version' => 1,
            'title' => '12% VAT: output tax on sales less input tax on purchases',
            'basis' => 'NIRC Sec. 106, 108, 110 as amended by RA 10963 and RA 11976 (EOPT) · RR 3-2024',
            'effective' => '2024-04-27',
        ],
        'IT-8PCT-PURE' => [
            'version' => 2,
            'title' => '8% income tax, purely self-employed',
            'basis' => 'NIRC Sec. 24(A)(2)(b) as amended by RA 10963 · RR 8-2018 Sec. 3 · RMO 23-2018',
            'effective' => '2018-01-01',
        ],
        'IT-8PCT-MIXED' => [
            'version' => 1,
            'title' => '8% income tax, job plus business (no ₱250,000 reduction)',
            'basis' => 'NIRC Sec. 24(A)(2)(c)(2) · RR 8-2018 Sec. 2(B)',
            'effective' => '2018-01-01',
        ],
        'IT-GRAD-2023' => [
            'version' => 1,
            'title' => 'Graduated income tax rates',
            'basis' => 'NIRC Sec. 24(A)(2)(a) as amended by RA 10963, table for 2023 onwards',
            'effective' => '2023-01-01',
        ],
        'IT-OSD' => [
            'version' => 1,
            'title' => 'Optional standard deduction, 40% of gross sales',
            'basis' => 'NIRC Sec. 34(L) · RR 16-2008',
            'effective' => '2018-01-01',
        ],
        'IT-ITEMIZED' => [
            'version' => 1,
            'title' => 'Itemized deductions, backed by invoices in your name',
            'basis' => 'NIRC Sec. 34(A) to (K)',
            'effective' => '2018-01-01',
        ],
        'PT-3PCT' => [
            'version' => 1,
            'title' => 'Percentage tax, 3% of quarterly gross sales',
            'basis' => 'NIRC Sec. 116 · rate back to 3% from July 1, 2023 (RA 11534 relief ended)',
            'effective' => '2023-07-01',
        ],
        'CWT-CREDIT' => [
            'version' => 1,
            'title' => 'Client withholding counts as paid',
            'basis' => 'NIRC Sec. 58, 76 · RR 2-98 as amended · RMC 14-2025',
            'effective' => '2018-01-01',
        ],
        'LIM-8PCT-3M' => [
            'version' => 1,
            'title' => 'The 8% option ends above ₱3,000,000',
            'basis' => 'RR 8-2018 Sec. 3(D) · NIRC Sec. 109(BB)',
            'effective' => '2018-01-01',
        ],
        'DL-1701Q' => [
            'version' => 1,
            'title' => 'Quarterly income tax due dates',
            'basis' => 'NIRC Sec. 74 · RR 8-2018',
            'effective' => '2018-01-01',
        ],
        'DL-WEEKEND-HOLIDAY' => [
            'version' => 1,
            'title' => 'Deadlines on a weekend or holiday move to the next working day',
            'basis' => 'Weekend rule',
            'effective' => '2018-01-01',
        ],
    ],

];
