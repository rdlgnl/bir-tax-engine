<?php

/*
|--------------------------------------------------------------------------
| Rule pack · Rule citations
|--------------------------------------------------------------------------
|
| Each rule the explanations cite: its title, legal basis and effective date.
|
| Part of config/tax.php, reviewed and versioned on its own: its version
| and sign-off are under `parts.citations` there. Amounts are in pesos.
|
*/

return [

    // Each rule's sign-off, once a tax professional has checked it:
    // 'reviewed' => ['by' => 'Name, CPA', 'on' => 'YYYY-MM-DD', 'source' => 'what it was checked against'].
    // Until then it's null, and the app says the rule isn't reviewed yet.
    'rules' => [
        'VAT-12PCT' => [
            'version' => 1,
            'title' => '12% VAT: output tax on sales less input tax on purchases',
            'basis' => 'NIRC Sec. 106, 108, 110 as amended by RA 10963 and RA 11976 (EOPT) · RR 3-2024',
            'effective' => '2024-04-27',
            'reviewed' => null,
        ],
        'IT-8PCT-PURE' => [
            'version' => 2,
            'title' => '8% income tax, purely self-employed',
            'basis' => 'NIRC Sec. 24(A)(2)(b) as amended by RA 10963 · RR 8-2018 Sec. 3 · RMO 23-2018',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'IT-8PCT-MIXED' => [
            'version' => 1,
            'title' => '8% income tax, job plus business (no ₱250,000 reduction)',
            'basis' => 'NIRC Sec. 24(A)(2)(c)(2) · RR 8-2018 Sec. 2(B)',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'IT-GRAD-2018' => [
            'version' => 1,
            'title' => 'Graduated income tax rates',
            'basis' => 'NIRC Sec. 24(A)(2)(a) as amended by RA 10963, table for 2018 to 2022',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'IT-GRAD-2023' => [
            'version' => 1,
            'title' => 'Graduated income tax rates',
            'basis' => 'NIRC Sec. 24(A)(2)(a) as amended by RA 10963, table for 2023 onwards',
            'effective' => '2023-01-01',
            'reviewed' => null,
        ],
        'IT-OSD' => [
            'version' => 1,
            'title' => 'Optional standard deduction, 40% of gross sales',
            'basis' => 'NIRC Sec. 34(L) · RR 16-2008',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'IT-ITEMIZED' => [
            'version' => 1,
            'title' => 'Itemized deductions, backed by invoices in your name',
            'basis' => 'NIRC Sec. 34(A) to (K)',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'PT-3PCT-2018' => [
            'version' => 1,
            'title' => 'Percentage tax, 3% of quarterly gross sales',
            'basis' => 'NIRC Sec. 116 as amended by RA 10963',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'PT-1PCT' => [
            'version' => 1,
            'title' => 'Percentage tax, 1% of quarterly gross sales',
            'basis' => 'NIRC Sec. 116 as amended by RA 11534 (CREATE), July 1, 2020 to June 30, 2023',
            'effective' => '2020-07-01',
            'reviewed' => null,
        ],
        'PT-3PCT' => [
            'version' => 1,
            'title' => 'Percentage tax, 3% of quarterly gross sales',
            'basis' => 'NIRC Sec. 116 · rate back to 3% from July 1, 2023 (RA 11534 relief ended)',
            'effective' => '2023-07-01',
            'reviewed' => null,
        ],
        'CWT-CREDIT' => [
            'version' => 1,
            'title' => 'Client withholding counts as paid',
            'basis' => 'NIRC Sec. 58, 76 · RR 2-98 as amended · RMC 14-2025',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'LIM-8PCT-3M' => [
            'version' => 1,
            'title' => 'The 8% option ends above ₱3,000,000',
            'basis' => 'RR 8-2018 Sec. 3(D) · NIRC Sec. 109(BB)',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'DL-1701Q' => [
            'version' => 1,
            'title' => 'Quarterly income tax due dates',
            'basis' => 'NIRC Sec. 74 · RR 8-2018',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
        'DL-WEEKEND-HOLIDAY' => [
            'version' => 1,
            'title' => 'Deadlines on a weekend or holiday move to the next working day',
            'basis' => 'Weekend rule',
            'effective' => '2018-01-01',
            'reviewed' => null,
        ],
    ],

];
