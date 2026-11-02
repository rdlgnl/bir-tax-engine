<?php

/*
|--------------------------------------------------------------------------
| BIR rule pack
|--------------------------------------------------------------------------
|
| Every rate, threshold, deadline and citation the tax engine uses. Nothing
| in app/Tax hard-codes a number: change it here and the whole app follows.
|
| The rules live in parts by kind in resources/rule-pack/ (rates,
| deadlines, holidays, form editions, tax codes, file formats, citations),
| each with its own version and sign-off under `parts`, so a CPA can
| review one kind at a time. config('tax.*') reads them as one.
|
| REVIEW STATUS: encoded from the NIRC as amended by RA 10963 (TRAIN) and
| the issuances cited in each part, and cross-checked against the figures on the
| approved screens. Nothing is reviewed until a tax professional signs
| off each part (under `parts`) and each rule (in citations.php); run
| `php artisan rules:status` to see what's left.
|
| Amounts are in pesos.
|
*/

return [

    'version' => '2026.11',

    // Each part of the pack, reviewed and versioned on its own. A part is
    // signed off once a tax professional has checked every rule in it:
    // 'reviewed' => ['by' => 'Name, CPA', 'on' => 'YYYY-MM-DD', 'source' => '…'].
    // The pack counts as reviewed when every part is.
    'parts' => [
        'rates' => ['title' => 'Rates and thresholds', 'version' => '2026.11', 'reviewed' => null],
        'deadlines' => ['title' => 'Deadlines', 'version' => '2026.11', 'reviewed' => null],
        'holidays' => ['title' => 'Holidays', 'version' => '2026.11', 'reviewed' => null],
        'forms' => ['title' => 'Form editions', 'version' => '2026.11', 'reviewed' => null],
        'codes' => ['title' => 'Tax codes', 'version' => '2026.11', 'reviewed' => null],
        'formats' => ['title' => 'File formats', 'version' => '2026.11', 'reviewed' => null],
        'citations' => ['title' => 'Rule citations', 'version' => '2026.11', 'reviewed' => null],
    ],

    // What each rule pack changed, newest first, for the "what changed" screen.
    // Each: area (Rules, Deadline calendar, Forms), title, summary.
    'changes' => [
        '2026.11' => [
            ['area' => 'Rules', 'title' => '8% counts sales when paid', 'summary' => 'On the 8% option a sale counts for income tax when the money is received, on cash or accrual books alike: RR 8-2018 Sec. 2(g) defines gross receipts as amounts actually or constructively received.'],
            ['area' => 'Deadline calendar', 'title' => '2027 holidays as proclaimed', 'summary' => 'Chinese New Year (February 6) and All Souls\' Day (November 2) are added to 2027, as Proclamation No. 1427, s. 2026 declares them. The Eid holidays come when they\'re declared.'],
            ['area' => 'Rules', 'title' => 'Which land leaves total assets', 'summary' => 'For the 20% corporate rate, total assets are net of depreciation and bad debts and leave out only the land under the office, plant and equipment; land held for sale or investment counts (RMC 62-2021).'],
            ['area' => 'Rules', 'title' => '15% on fees to larger companies', 'summary' => 'Professional fees paid to a company with gross income over ₱720,000 are withheld at 15% (WC011); 10% (WC010) stays for ₱720,000 or less.'],
            ['area' => 'Rules', 'title' => 'Payroll on the BIR withholding table', 'summary' => 'Each month\'s tax on salaries follows the BIR table (RR 11-2018 Annex "E"), with the year-end adjustment in December. Contributions, de minimis benefits, a minimum wage and the 13th month up to ₱90,000 aren\'t taxed.'],
            ['area' => 'Rules', 'title' => 'Each year on its own rules', 'summary' => 'Years before 2023 use the 2018–2022 graduated table, percentage tax is 1% from July 2020 to June 2023, and corporations follow the CREATE rates and MCIT of each quarter. Years before 2018 (2021 for corporations) aren\'t computed.'],
        ],
        '2026.10' => [
            ['area' => 'Rules', 'title' => 'First rule pack', 'summary' => '8% option, graduated rates (2023 table), the 40% OSD, 3% percentage tax and the ₱3,000,000 limits.'],
            ['area' => 'Deadline calendar', 'title' => 'Deadlines and holidays through 2027', 'summary' => 'Due dates that fall on weekends or holidays move to the next working day.'],
            ['area' => 'Forms', 'title' => 'BIR forms', 'summary' => '1701Q, 1701-MS, 1701A, 1701 and 2551Q, with 1905 and 1900 for registration.'],
        ],
    ],

    ...require __DIR__.'/../resources/rule-pack/rates.php',
    ...require __DIR__.'/../resources/rule-pack/deadlines.php',
    ...require __DIR__.'/../resources/rule-pack/holidays.php',
    ...require __DIR__.'/../resources/rule-pack/forms.php',
    ...require __DIR__.'/../resources/rule-pack/codes.php',
    ...require __DIR__.'/../resources/rule-pack/formats.php',
    ...require __DIR__.'/../resources/rule-pack/citations.php',

];
