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

    'micro_taxpayer_limit' => 3_000_000,

    // Rates clients are expected to withhold, shown as hints only.
    'expected_withholding' => [
        'services' => 0.05,
        'services_without_declaration' => 0.10,
        'products' => 0.01,
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
        // Sworn declaration to clients: month and day of each year.
        'sworn_declaration' => [1, 15],
    ],

    // Non-working days that push a deadline to the next working day.
    // Regular holidays plus the usual special non-working days. Check each
    // year's proclamation before relying on these.
    'holidays' => [
        '2026-01-01', '2026-02-17', '2026-04-02', '2026-04-03', '2026-04-04',
        '2026-04-09', '2026-05-01', '2026-06-12', '2026-08-21', '2026-08-31',
        '2026-11-01', '2026-11-30', '2026-12-08', '2026-12-24', '2026-12-25',
        '2026-12-30', '2026-12-31',
        '2027-01-01', '2027-03-25', '2027-03-26', '2027-03-27', '2027-04-09',
        '2027-05-01', '2027-06-12', '2027-08-21', '2027-08-30', '2027-11-01',
        '2027-11-30', '2027-12-08', '2027-12-24', '2027-12-25', '2027-12-30',
        '2027-12-31',
    ],

    'rules' => [
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
