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

    'version' => '2026.11',

    'reviewed' => false,

    // What each rule pack changed, newest first, for the "what changed" screen.
    // Each: area (Rules, Deadline calendar, Forms), title, summary.
    'changes' => [
        '2026.11' => [
            ['area' => 'Rules', 'title' => 'Payroll on the BIR withholding table', 'summary' => 'Each month\'s tax on salaries follows the BIR table (RR 11-2018 Annex "E"), with the year-end adjustment in December. Contributions, de minimis benefits, a minimum wage and the 13th month up to ₱90,000 aren\'t taxed.'],
            ['area' => 'Rules', 'title' => 'Each year on its own rules', 'summary' => 'Years before 2023 use the 2018–2022 graduated table, percentage tax is 1% from July 2020 to June 2023, and corporations follow the CREATE rates and MCIT of each quarter. Years before 2018 (2021 for corporations) aren\'t computed.'],
        ],
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

    'osd_rate' => 0.40,

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
            // the limits the employee page lists. REVIEW: RR 4-2025 raised clothing to
            // ₱7,000 in 2025; its effective date isn't encoded.
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
        // app supports: 2020 straddles the change and isn't encoded.
        // REVIEW: the 1% MCIT for July 2020 to June 2023.
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
        // RR 29-2025: higher de minimis limits from January 6, 2026.
        '2026-01-06' => [
            'de_minimis' => [
                'rice' => ['amount' => 2_500],
                'clothing' => ['amount' => 8_000],
                'medical_dependents' => ['amount' => 333],
                'laundry' => ['amount' => 400],
            ],
        ],
    ],

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
    // ₱5,000,000 or less and total assets (excluding land) are ₱100,000,000
    // or less. From the 4th year after operations began, the tax is at least
    // the MCIT on gross income. The rates are in `dated`. The quarterly 1702Q
    // is due 60 days after each of the first three quarters. REVIEW: rates,
    // thresholds, the MCIT start year, and testing the lower rate on the
    // taxable income to date each quarter.
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

    // Books of accounts (NIRC Sec. 232 and 235, as amended by EOPT): kept 5
    // years. Computerized books are required only of large taxpayers
    // (RR 9-2009); anyone may register them through ORUS.
    'books' => [
        'retention_years' => 5,
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
    // 22, 2026). Micro taxpayers aren't covered.
    // REVIEW: the circular's number wasn't found.
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

    // Financial statements with the annual return (RMC 17-2019): none on 8%
    // or the 40% deduction; with itemized deductions, statements (or an
    // Account Information Form), audited by an independent CPA once gross
    // sales exceed this (NIRC Sec. 232(A) as TRAIN amended it). They go
    // through eAFS with the 2307s, within 15 days after the deadline.
    'financial_statements' => [
        'audit_above' => 3_000_000,
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
        '2000' => ['edition' => 'January 2018', 'template' => '2000-2018-01', 'whole_pesos' => false],
        '1601-C' => ['edition' => 'January 2018 (ENCS)', 'template' => '1601-C-2018-01', 'whole_pesos' => false],
        '2316' => ['edition' => 'September 2021 (ENCS)', 'template' => '2316-2021-09', 'whole_pesos' => false],
        '1604-C' => ['edition' => 'January 2018', 'template' => '1604-C-2018-01', 'whole_pesos' => false],
        '2307' => ['edition' => 'January 2018 (ENCS)', 'template' => '2307-2018-01', 'whole_pesos' => false],
        // A corporation the taxpayer runs.
        '1702Q' => ['edition' => 'January 2018 (ENCS)', 'template' => '1702Q-2018-01', 'whole_pesos' => true],
        '1702-RT' => ['edition' => 'January 2018 (ENCS)', 'template' => '1702-RT-2018-01', 'whole_pesos' => true],
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
        'IT-GRAD-2018' => [
            'version' => 1,
            'title' => 'Graduated income tax rates',
            'basis' => 'NIRC Sec. 24(A)(2)(a) as amended by RA 10963, table for 2018 to 2022',
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
        'PT-3PCT-2018' => [
            'version' => 1,
            'title' => 'Percentage tax, 3% of quarterly gross sales',
            'basis' => 'NIRC Sec. 116 as amended by RA 10963',
            'effective' => '2018-01-01',
        ],
        'PT-1PCT' => [
            'version' => 1,
            'title' => 'Percentage tax, 1% of quarterly gross sales',
            'basis' => 'NIRC Sec. 116 as amended by RA 11534 (CREATE), July 1, 2020 to June 30, 2023',
            'effective' => '2020-07-01',
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
