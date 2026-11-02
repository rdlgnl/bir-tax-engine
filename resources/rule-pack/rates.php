<?php

/*
|--------------------------------------------------------------------------
| Rule pack · Rates and thresholds
|--------------------------------------------------------------------------
|
| The rates, limits and dated rules the tax engine computes with: the 8% option, OSD, the graduated and withholding tables by year, VAT, DST, corporations, penalties, the taxpayer classes, and the time-limited relief.
|
| Part of config/tax.php, reviewed and versioned on its own: its version
| and sign-off are under `parts.rates` there. Amounts are in pesos.
|
*/

return [

    'eight_percent' => [
        'rate' => 0.08,
        // Only purely self-employed individuals get this reduction.
        'exempt_amount' => 250_000,
        // Above this the 8% option ends for the whole year (VAT threshold).
        'gross_limit' => 3_000_000,
    ],

    'osd_rate' => 0.40,

    // From this date percentage tax and VAT count a sale when it's invoiced
    // (RA 11976 Secs. 17, 24; RR 3-2024, effective April 27, 2024). Before,
    // services counted when paid. Income tax follows the books' method.
    'sales_by_invoice_from' => '2024-04-27',

    // Rules that changed over the years, by the date each took effect. A
    // period uses every entry on or before it (App\Tax\Services\TaxRules):
    // a year's income tax its last day, a quarter's percentage tax and MCIT
    // its first day. A period before the first entry has no rules and the
    // app refuses it, as it does corporations before 2021. `rule_ids` name
    // the rules the explanations cite.
    // Graduated tables: [over, base tax, rate on the excess], per year.
    'dated' => [
        // RA 10963 (TRAIN): the table for 2018 to 2022, and 3% percentage tax.
        '2018-01-01' => [
            'graduated_brackets' => [
                [0, 0, 0.00],
                [250_000, 0, 0.20],
                [400_000, 30_000, 0.25],
                [800_000, 130_000, 0.30],
                [2_000_000, 490_000, 0.32],
                [8_000_000, 2_410_000, 0.35],
            ],
            'percentage_tax_rate' => 0.03,
            'rule_ids' => ['graduated' => 'IT-GRAD-2018', 'percentage_tax' => 'PT-3PCT-2018'],
            // DST on loans (Sec. 179 as TRAIN amended it): ₱1.50 for each ₱200 or fraction.
            'dst' => ['loan' => ['per' => 200, 'rate' => 1.50]],
            // Tax withheld on a month's taxable pay: RR 11-2018 Annex "D", for pay
            // from 2018 to 2022. Same shape as the graduated table.
            // REVIEW: copied from the annex as summarized, not from the printed table.
            'compensation_brackets' => [
                [0, 0, 0.00],
                [20_833, 0, 0.20],
                [33_333, 2_500, 0.25],
                [66_667, 10_833.33, 0.30],
                [166_667, 40_833.33, 0.32],
                [666_667, 200_833.33, 0.35],
            ],
            // De minimis benefits an employer can pay untaxed (RR 11-2018 Sec. 2.78.1(A)(3)):
            // the limits the employee page lists, raised by RR 29-2025 below.
            // REVIEW: RR 4-2025 raised clothing to ₱7,000 during 2025; its
            // effective date wasn't found, so 2025 keeps ₱6,000.
            'de_minimis' => [
                'rice' => ['amount' => 2_000, 'per' => 'month', 'label' => 'Rice subsidy'],
                'clothing' => ['amount' => 6_000, 'per' => 'year', 'label' => 'Uniform and clothing'],
                'medical_dependents' => ['amount' => 250, 'per' => 'month', 'label' => 'Medical cash allowance to dependents'],
                'laundry' => ['amount' => 300, 'per' => 'month', 'label' => 'Laundry allowance'],
            ],
        ],
        // RA 11534 (CREATE): 1% percentage tax from July 1, 2020 to June 30, 2023.
        '2020-07-01' => [
            'percentage_tax_rate' => 0.01,
            'rule_ids' => ['percentage_tax' => 'PT-1PCT'],
        ],
        // RA 11534 (CREATE) for corporations, from the first full year the
        // app supports: 2020 straddles the change and isn't encoded. The 1%
        // MCIT runs July 1, 2020 to June 30, 2023 (RR 5-2021's rate table).
        '2021-01-01' => [
            'corporation' => ['rate' => 0.25, 'small_rate' => 0.20, 'mcit_rate' => 0.01],
        ],
        // RA 10963 (TRAIN): the table for 2023 onwards.
        '2023-01-01' => [
            'graduated_brackets' => [
                [0, 0, 0.00],
                [250_000, 0, 0.15],
                [400_000, 22_500, 0.20],
                [800_000, 102_500, 0.25],
                [2_000_000, 402_500, 0.30],
                [8_000_000, 2_202_500, 0.35],
            ],
            'rule_ids' => ['graduated' => 'IT-GRAD-2023'],
            // RR 11-2018 Annex "E", for pay from January 1, 2023.
            'compensation_brackets' => [
                [0, 0, 0.00],
                [20_833, 0, 0.15],
                [33_333, 1_875, 0.20],
                [66_667, 8_541.80, 0.25],
                [166_667, 33_541.80, 0.30],
                [666_667, 183_541.80, 0.35],
            ],
        ],
        // The CREATE relief ended: 3% percentage tax and 2% MCIT again.
        '2023-07-01' => [
            'percentage_tax_rate' => 0.03,
            'corporation' => ['mcit_rate' => 0.02],
            'rule_ids' => ['percentage_tax' => 'PT-3PCT'],
        ],
        // RA 12214 (CMEPA) and RR 19-2025: DST on loans is 0.75% of the
        // issue price, without rounding up to the next ₱200.
        '2025-07-01' => [
            'dst' => ['loan' => ['percent' => 0.0075]],
        ],
        // RR 29-2025: higher de minimis limits from January 6, 2026 (as KPMG
        // reports it; ₱2,000 a semester for dependents' medical).
        '2026-01-06' => [
            'de_minimis' => [
                'rice' => ['amount' => 2_500],
                'clothing' => ['amount' => 8_000],
                'medical_dependents' => ['amount' => 333],
                'laundry' => ['amount' => 400],
            ],
        ],
    ],

    // Value-added tax, for VAT-registered taxpayers. Amounts are kept net of
    // VAT; only purchase invoices in the taxpayer's name can be claimed.
    'vat' => [
        'rate' => 0.12,
    ],

    // Taxpayer classes by gross sales, net of VAT (RA 11976; RR 8-2024). The
    // BIR assigns each taxpayer's class; these only estimate it.
    'classification' => [
        'micro_below' => 3_000_000,
        'small_below' => 20_000_000,
        'medium_below' => 1_000_000_000,
    ],

    // Documentary stamp tax on a loan (NIRC section 179): the rate is in
    // `dated`, by the date the loan was signed, and a term under a year pays
    // its share by days over 365. Filed on Form 2000 within 5 days after the
    // month it was signed (Sec. 200), ATC DS106.
    // REVIEW: the ATC on loans signed from July 1, 2025.
    'dst' => [
        'loan' => ['atc' => 'DS106'],
        'due_days_after_month' => 5,
    ],

    // A corporation's income tax (NIRC section 27, as the CREATE Act changed
    // it): the regular rate, or the lower rate when taxable income is
    // ₱5,000,000 or less and total assets are ₱100,000,000 or less, net of
    // depreciation and excluding only the land under the office, plant and
    // equipment (RR 5-2021; RMC 62-2021). From the 4th taxable year after
    // the year it registered with the BIR, the tax is at least the MCIT on
    // gross income (RMC 62-2021). The rates are in `dated`. The quarterly
    // 1702Q is due 60 days after each of the first three quarters.
    // REVIEW: testing the lower rate on the taxable income to date each
    // quarter; neither RR 5-2021 nor RMC 62-2021 says how the 1702Q applies it.
    'corporation' => [
        'small_taxable_income' => 5_000_000,
        'small_total_assets' => 100_000_000,
        'mcit_from_year' => 4,
        'osd_rate' => 0.40,
        'quarterly_due_days' => 60,
        'atc' => ['regular' => 'IC010', 'mcit' => 'IC055'],
    ],

    // Paying late (NIRC sections 248-249): a surcharge once, and interest a
    // year on the unpaid tax from the due date. Micro and small taxpayers pay
    // a 10% surcharge and half the interest, for returns due from April 27,
    // 2024 (RA 11976; RR 6-2024). A compromise penalty may be added by the
    // BIR and isn't estimated.
    // REVIEW: interest on tax that was unpaid on both sides of April 27, 2024.
    'penalties' => [
        'surcharge' => 0.25,
        'interest' => 0.12,
        'reduced_surcharge' => 0.10,
        'reduced_interest' => 0.06,
        'reduced_from' => '2024-04-27',
    ],

    // Rates clients are expected to withhold, shown as hints only.
    'expected_withholding' => [
        'services' => 0.05,
        'services_without_declaration' => 0.10,
        'products' => 0.01,
    ],

    // Financial statements with the annual return (RMC 17-2019): none on 8%
    // or the 40% deduction; with itemized deductions, statements (or an
    // Account Information Form), audited by an independent CPA once gross
    // sales exceed this (NIRC Sec. 232(A) as TRAIN amended it). They go
    // through eAFS with the 2307s, within 15 days after the deadline.
    'financial_statements' => [
        'audit_above' => 3_000_000,
    ],

    // RR 4-2026 (RMC 84-2026): a one-time abatement for micro taxpayers of
    // the tax and penalties of a taxable year, up to the ceiling, delinquent
    // or under an open case as of `cases_as_of`; the fee is paid on Form 0605.
    'abatement' => [
        'cases_as_of' => '2025-12-31',
        'until' => '2026-12-31',
        'ceiling' => 80_000,
        'fee' => 5_000,
        'source' => 'RR 4-2026',
    ],

    // The BIR's Electronic Invoicing System (EIS) is required by this date of
    // small, medium and large taxpayers selling online, and of those with
    // computerized books or under the Large Taxpayers Service (BIR, September
    // 22, 2026; RMC 98-2026). Micro taxpayers aren't covered.
    'eis' => [
        'required_by' => '2026-12-31',
    ],

    // How returns are filed (RMC 20-2026): taxpayers not on eFPS file
    // through eBIRForms, no-payment returns included. Paper is for the
    // 1701-MS of micro and small taxpayers, and otherwise only when the BIR
    // declares its systems down. Any other filled form is a worksheet to
    // copy from. REVIEW: check each year's filing guidelines.
    'filing' => [
        'paper_forms' => ['1701-MS'],
    ],

    // Books of accounts (NIRC Sec. 232 and 235, as amended by EOPT): kept 5
    // years. Computerized books are required only of large taxpayers
    // (RR 9-2009); anyone may register them through ORUS.
    'books' => [
        'retention_years' => 5,
        // Loose-leaf books are bound within this many days after year end (BIR Form 1900).
        'bind_within_days' => 15,
    ],

];
