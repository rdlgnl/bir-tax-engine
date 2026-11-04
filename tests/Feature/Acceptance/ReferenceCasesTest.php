<?php

use App\Corporations\Models\Corporation;
use App\Corporations\Services\CorporateTax;
use App\Payroll\Models\Employee;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Reference cases for the CPA (review task C3)
|--------------------------------------------------------------------------
|
| Typical taxpayers and the returns the app prepares for them, line by
| line, each filed on its due date and paid in full unless the case says
| otherwise. docs/REFERENCE-CASES.md is rendered from these cases for the
| CPA to check. Until the CPA signs off, the expected figures are the
| app's own; a corrected figure goes in `expected`, and the app is fixed
| until the case passes.
|
| After changing a case, rewrite the document:
| REFERENCE_CASES_WRITE=1 php artisan test --filter=ReferenceCases
|
*/

/**
 * @return array<string, array{title: string, facts: list<string>, ask?: string, figures: Closure(TestCase): array<string, list<array{string, float}>>, expected: array<string, list<array{string, float}>>}>
 */
function referenceCases(): array
{
    return [
        'R1' => [
            'title' => 'Freelance designer on the 8% option',
            'facts' => [
                'Purely self-employed, registered with the BIR in 2024; not VAT-registered.',
                'Elected the 8% option on the Q1 2026 return.',
                'Sales in 2026: ₱300,000 in Q1; ₱350,000 in Q2, of which ₱100,000 from Acme Corp., which withheld ₱5,000 (5%) and gave a 2307; ₱250,000 in Q3; ₱300,000 in Q4. ₱1,200,000 for the year.',
            ],
            'figures' => function (TestCase $t) {
                $profile = TaxProfile::factory()->create();
                refSales(['2026-02-10' => 300_000, '2026-05-10' => 250_000, '2026-08-10' => 250_000, '2026-11-10' => 300_000]);
                Transaction::factory()->on('2026-05-20')->withheld(5_000, '2026-07-15')->create(['counterparty' => 'Acme Corp.', 'amount' => 100_000]);

                return [
                    ...refFileOnTime($t, '1701Q', 2026, 1),
                    ...refFileOnTime($t, '1701Q', 2026, 2),
                    ...refFileOnTime($t, '1701Q', 2026, 3),
                    ...refFileOnTime($t, refAnnualForm($profile, 2026), 2026, 4),
                ];
            },
            'expected' => [
                '1701Q, Q1 2026, filed May 15, 2026' => [
                    ['Sales/receipts, this quarter', 300000.0],
                    ['Sales/receipts, previous quarters', 0.0],
                    ['Less: allowable reduction from gross sales', 250000.0],
                    ['Taxable income to date', 50000.0],
                    ['Tax due (8%)', 4000.0],
                    ['Less: tax paid in previous quarters', 0.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 4000.0],
                ],
                '1701Q, Q2 2026, filed August 17, 2026' => [
                    ['Sales/receipts, this quarter', 350000.0],
                    ['Sales/receipts, previous quarters', 300000.0],
                    ['Less: allowable reduction from gross sales', 250000.0],
                    ['Taxable income to date', 400000.0],
                    ['Tax due (8%)', 32000.0],
                    ['Less: tax paid in previous quarters', 4000.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 5000.0],
                    ['Tax payable', 23000.0],
                ],
                '1701Q, Q3 2026, filed November 16, 2026' => [
                    ['Sales/receipts, this quarter', 250000.0],
                    ['Sales/receipts, previous quarters', 650000.0],
                    ['Less: allowable reduction from gross sales', 250000.0],
                    ['Taxable income to date', 650000.0],
                    ['Tax due (8%)', 52000.0],
                    ['Less: tax paid in previous quarters', 27000.0],
                    ['Less: creditable tax withheld, previous quarters', 5000.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 20000.0],
                ],
                '1701-MS, 2026 annual, filed April 15, 2027' => [
                    ['Sales/receipts for the year', 1200000.0],
                    ['Less: allowable reduction from gross sales', 250000.0],
                    ['Taxable income to date', 950000.0],
                    ['Tax due (8%)', 76000.0],
                    ['Less: tax paid in Q1 to Q3 returns', 47000.0],
                    ['Less: creditable tax withheld for the year', 5000.0],
                    ['Tax payable', 24000.0],
                ],
            ],
        ],
        'R2' => [
            'title' => 'Consultant on graduated rates with the 40% optional standard deduction',
            'facts' => [
                'Purely self-employed, registered for income tax and percentage tax; not VAT-registered.',
                'Graduated rates with the optional standard deduction (OSD) for 2026.',
                'Sales in 2026: ₱400,000 in Q1, ₱500,000 in Q2, ₱450,000 in Q3, ₱450,000 in Q4. ₱1,800,000 for the year. No tax withheld by clients.',
            ],
            'figures' => function (TestCase $t) {
                $profile = TaxProfile::factory()->graduated(DeductionMethod::Osd)->create();
                refSales(['2026-02-10' => 400_000, '2026-05-10' => 500_000, '2026-08-10' => 450_000, '2026-11-10' => 450_000]);

                return [
                    ...refFileOnTime($t, '2551Q', 2026, 1),
                    ...refFileOnTime($t, '1701Q', 2026, 1),
                    ...refFileOnTime($t, '1701Q', 2026, 2),
                    ...refFileOnTime($t, '1701Q', 2026, 3),
                    ...refFileOnTime($t, refAnnualForm($profile, 2026), 2026, 4),
                ];
            },
            'expected' => [
                '2551Q, Q1 2026, filed April 27, 2026' => [
                    ['Taxable amount (sales, this quarter)', 400000.0],
                    ['Tax due (3%)', 12000.0],
                    ['Less: tax credits / payments', 0.0],
                    ['Tax payable', 12000.0],
                ],
                '1701Q, Q1 2026, filed May 15, 2026' => [
                    ['Sales/receipts, this quarter', 400000.0],
                    ['Sales/receipts, previous quarters', 0.0],
                    ['Less: optional standard deduction (40%)', 160000.0],
                    ['Taxable income to date', 240000.0],
                    ['Tax due (graduated rates)', 0.0],
                    ['Less: tax paid in previous quarters', 0.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 0.0],
                ],
                '1701Q, Q2 2026, filed August 17, 2026' => [
                    ['Sales/receipts, this quarter', 500000.0],
                    ['Sales/receipts, previous quarters', 400000.0],
                    ['Less: optional standard deduction (40%)', 360000.0],
                    ['Taxable income to date', 540000.0],
                    ['Tax due (graduated rates)', 50500.0],
                    ['Less: tax paid in previous quarters', 0.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 50500.0],
                ],
                '1701Q, Q3 2026, filed November 16, 2026' => [
                    ['Sales/receipts, this quarter', 450000.0],
                    ['Sales/receipts, previous quarters', 900000.0],
                    ['Less: optional standard deduction (40%)', 540000.0],
                    ['Taxable income to date', 810000.0],
                    ['Tax due (graduated rates)', 105000.0],
                    ['Less: tax paid in previous quarters', 50500.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 54500.0],
                ],
                '1701-MS, 2026 annual, filed April 15, 2027' => [
                    ['Sales/receipts for the year', 1800000.0],
                    ['Less: optional standard deduction (40%)', 720000.0],
                    ['Taxable income to date', 1080000.0],
                    ['Tax due (graduated rates)', 172500.0],
                    ['Less: tax paid in Q1 to Q3 returns', 105000.0],
                    ['Less: creditable tax withheld for the year', 0.0],
                    ['Tax payable', 67500.0],
                ],
            ],
        ],
        'R3' => [
            'title' => 'Professional on graduated rates with itemized deductions',
            'facts' => [
                'Purely self-employed, registered for income tax and percentage tax; not VAT-registered; books kept on the accrual method.',
                'Graduated rates with itemized deductions for 2026.',
                'Sales of ₱250,000 each quarter, ₱1,000,000 for the year. Expenses of ₱90,000 each quarter (office rent and supplies), each with an invoice in the taxpayer\'s name: ₱360,000 for the year.',
            ],
            'ask' => "The 3% percentage tax paid (₱7,500 a quarter) isn't among the itemized deductions. Should it be deducted as a tax on the business (NIRC Sec. 34(C))?",
            'figures' => function (TestCase $t) {
                $profile = TaxProfile::factory()->graduated(DeductionMethod::Itemized)->create();
                refSales(['2026-02-10' => 250_000, '2026-05-10' => 250_000, '2026-08-10' => 250_000, '2026-11-10' => 250_000]);

                foreach (['2026-03-01', '2026-06-01', '2026-09-01', '2026-12-01'] as $date) {
                    Transaction::factory()->expense(validInvoice: true)->on($date)->create(['counterparty' => 'Office rent and supplies', 'amount' => 90_000]);
                }

                refFileOnTime($t, '1701Q', 2026, 1);
                refFileOnTime($t, '1701Q', 2026, 2);

                return [
                    ...refFileOnTime($t, '2551Q', 2026, 3),
                    ...refFileOnTime($t, '1701Q', 2026, 3),
                    ...refFileOnTime($t, refAnnualForm($profile, 2026), 2026, 4),
                ];
            },
            'expected' => [
                '2551Q, Q3 2026, filed October 26, 2026' => [
                    ['Taxable amount (sales, this quarter)', 250000.0],
                    ['Tax due (3%)', 7500.0],
                    ['Less: tax credits / payments', 0.0],
                    ['Tax payable', 7500.0],
                ],
                '1701Q, Q3 2026, filed November 16, 2026' => [
                    ['Sales/receipts, this quarter', 250000.0],
                    ['Sales/receipts, previous quarters', 500000.0],
                    ['Less: allowable itemized deductions', 270000.0],
                    ['Taxable income to date', 480000.0],
                    ['Tax due (graduated rates)', 38500.0],
                    ['Less: tax paid in previous quarters', 10500.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 28000.0],
                ],
                '1701-MS, 2026 annual, filed April 15, 2027' => [
                    ['Sales/receipts for the year', 1000000.0],
                    ['Less: allowable itemized deductions', 360000.0],
                    ['Taxable income to date', 640000.0],
                    ['Tax due (graduated rates)', 70500.0],
                    ['Less: tax paid in Q1 to Q3 returns', 38500.0],
                    ['Less: creditable tax withheld for the year', 0.0],
                    ['Tax payable', 32000.0],
                ],
            ],
        ],
        'R4' => [
            'title' => 'Employee with a side business on the 8% option',
            'facts' => [
                'Employed all of 2026 at ₱50,000 a month (₱600,000 for the year, no 13th month recorded), with the employer withholding the full tax on it.',
                'A side business on the 8% option: ₱40,000 in Q1, ₱40,000 in Q2, ₱90,000 in Q3, ₱30,000 in Q4. ₱200,000 for the year.',
                'With a salary, the ₱250,000 reduction doesn\'t apply to the business income.',
            ],
            'figures' => function (TestCase $t) {
                $profile = TaxProfile::factory()->withJob()->create();
                refSales(['2026-02-10' => 40_000, '2026-05-10' => 40_000, '2026-08-10' => 90_000, '2026-11-10' => 30_000]);

                refFileOnTime($t, '1701Q', 2026, 1);
                refFileOnTime($t, '1701Q', 2026, 2);

                return [
                    ...refFileOnTime($t, '1701Q', 2026, 3),
                    ...refFileOnTime($t, refAnnualForm($profile, 2026), 2026, 4),
                ];
            },
            'expected' => [
                '1701Q, Q3 2026, filed November 16, 2026' => [
                    ['Sales/receipts, this quarter', 90000.0],
                    ['Sales/receipts, previous quarters', 80000.0],
                    ['Less: allowable reduction (none with a job)', 0.0],
                    ['Taxable income to date', 170000.0],
                    ['Tax due (8%)', 13600.0],
                    ['Less: tax paid in previous quarters', 6400.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 7200.0],
                ],
                '1701, 2026 annual, filed April 15, 2027' => [
                    ['Sales/receipts for the year', 200000.0],
                    ['Less: allowable reduction (none with a job)', 0.0],
                    ['Taxable business income', 200000.0],
                    ['Tax due on business (8%)', 16000.0],
                    ['Taxable salary (Northwind BPO Inc.)', 600000.0],
                    ['Tax due on salary (graduated rates)', 62500.0],
                    ['Total tax due', 78500.0],
                    ['Less: tax paid in Q1 to Q3 returns', 13600.0],
                    ['Less: creditable tax withheld for the year', 0.0],
                    ['Less: tax withheld by your employer (2316)', 62500.0],
                    ['Tax payable', 2400.0],
                ],
            ],
        ],
        'R5' => [
            'title' => 'Sales pass ₱3,000,000 on the 8% option',
            'facts' => [
                'Purely self-employed on the 8% option, registered for percentage tax; not VAT-registered.',
                'Sales of ₱1,000,000 in February and ₱1,000,000 in May; ₱800,000 in July and ₱500,000 in August, passing ₱3,000,000 in August.',
                'A ₱500,000 expense in September with an invoice in the taxpayer\'s name.',
                'Q1 and Q2 were filed and paid on time at 8%.',
            ],
            'ask' => 'The Q3 2551Q carries percentage tax on all sales from January to August, and the Q3 1701Q switches to graduated rates with itemized deductions for the year to date, crediting the 8% tax paid in Q1 and Q2 (RR 8-2018). Is that how both should be filed?',
            'figures' => function (TestCase $t) {
                TaxProfile::factory()->create();
                refSales(['2026-02-10' => 1_000_000, '2026-05-10' => 1_000_000, '2026-07-10' => 800_000, '2026-08-10' => 500_000]);
                Transaction::factory()->expense(validInvoice: true)->on('2026-09-05')->create(['counterparty' => 'Studio rent', 'amount' => 500_000]);

                refFileOnTime($t, '1701Q', 2026, 1);
                refFileOnTime($t, '1701Q', 2026, 2);

                return [
                    ...refFileOnTime($t, '2551Q', 2026, 3),
                    ...refFileOnTime($t, '1701Q', 2026, 3),
                ];
            },
            'expected' => [
                '2551Q, Q3 2026, filed October 26, 2026' => [
                    ['Taxable amount (sales, this quarter)', 3300000.0],
                    ['Tax due (3%)', 99000.0],
                    ['Less: tax credits / payments', 0.0],
                    ['Tax payable', 99000.0],
                ],
                '1701Q, Q3 2026, filed November 16, 2026' => [
                    ['Sales/receipts, this quarter', 1300000.0],
                    ['Sales/receipts, previous quarters', 2000000.0],
                    ['Less: allowable itemized deductions', 500000.0],
                    ['Taxable income to date', 2800000.0],
                    ['Tax due (graduated rates)', 642500.0],
                    ['Less: tax paid in previous quarters', 140000.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 502500.0],
                ],
            ],
        ],
        'R6' => [
            'title' => 'VAT-registered professional',
            'facts' => [
                'Purely self-employed, VAT-registered (so not registered for percentage tax), on graduated rates with the OSD.',
                'Q3 2026 sales: ₱500,000 in July and ₱300,000 in August, each plus 12% VAT (₱60,000 and ₱36,000).',
                'Q3 purchases: ₱200,000 plus ₱24,000 VAT in August, with a VAT invoice in the taxpayer\'s name. No sales or purchases in Q1 and Q2.',
            ],
            'figures' => function (TestCase $t) {
                TaxProfile::factory()->graduated(DeductionMethod::Osd)->create(['vat_registered' => true, 'registered_percentage_tax' => false]);

                foreach (['2026-07-10' => 500_000, '2026-08-10' => 300_000] as $date => $amount) {
                    Transaction::query()->create([
                        'type' => TransactionType::Payment, 'occurred_on' => $date, 'counterparty' => 'Lumen Digital Inc.', 'counterparty_tin' => '008-123-456-000',
                        'amount' => $amount, 'vat_kind' => 'vatable', 'vat_amount' => $amount * 0.12,
                    ]);
                }

                Transaction::query()->create([
                    'type' => TransactionType::Expense, 'occurred_on' => '2026-08-20', 'counterparty' => 'Software supplier', 'category' => 'software',
                    'amount' => 200_000, 'vat_amount' => 24_000, 'has_valid_invoice' => true,
                ]);

                return [
                    ...refFileOnTime($t, '2550Q', 2026, 3),
                    ...refFileOnTime($t, '1701Q', 2026, 3),
                ];
            },
            'expected' => [
                '2550Q, Q3 2026, filed October 26, 2026' => [
                    ['VATable sales', 800000.0],
                    ['Output VAT (12%)', 96000.0],
                    ['Domestic purchases', 200000.0],
                    ['Less: input VAT on purchases', 24000.0],
                    ['VAT payable', 72000.0],
                ],
                '1701Q, Q3 2026, filed November 16, 2026' => [
                    ['Sales/receipts, this quarter', 800000.0],
                    ['Sales/receipts, previous quarters', 0.0],
                    ['Less: optional standard deduction (40%)', 320000.0],
                    ['Taxable income to date', 480000.0],
                    ['Tax due (graduated rates)', 38500.0],
                    ['Less: tax paid in previous quarters', 0.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 38500.0],
                ],
            ],
        ],
        'R7' => [
            'title' => 'A 2022 quarter, on the rules of that year',
            'facts' => [
                'Purely self-employed since 2019, on graduated rates with the OSD, registered for percentage tax.',
                'Sales of ₱300,000 in Q1 2022 and ₱400,000 in Q2 2022.',
                'For 2022: the 2018–2022 graduated table, and percentage tax at 1% (CREATE, July 2020 to June 2023).',
            ],
            'figures' => function (TestCase $t) {
                TaxProfile::factory()->graduated(DeductionMethod::Osd)->create(['registered_on' => '2019-01-15']);
                refSales(['2022-02-10' => 300_000, '2022-05-10' => 400_000]);

                refFileOnTime($t, '1701Q', 2022, 1);

                return [
                    ...refFileOnTime($t, '2551Q', 2022, 2),
                    ...refFileOnTime($t, '1701Q', 2022, 2),
                ];
            },
            'expected' => [
                '2551Q, Q2 2022, filed July 25, 2022' => [
                    ['Taxable amount (sales, this quarter)', 400000.0],
                    ['Tax due (1%)', 4000.0],
                    ['Less: tax credits / payments', 0.0],
                    ['Tax payable', 4000.0],
                ],
                '1701Q, Q2 2022, filed August 15, 2022' => [
                    ['Sales/receipts, this quarter', 400000.0],
                    ['Sales/receipts, previous quarters', 300000.0],
                    ['Less: optional standard deduction (40%)', 280000.0],
                    ['Taxable income to date', 420000.0],
                    ['Tax due (graduated rates)', 35000.0],
                    ['Less: tax paid in previous quarters', 0.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 35000.0],
                ],
            ],
        ],
        'R8' => [
            'title' => 'A quarterly return filed a month late',
            'facts' => [
                'Purely self-employed on the 8% option; a micro taxpayer (sales under ₱3,000,000), so the reduced EOPT penalties apply.',
                'Sales of ₱400,000 in Q1 2026.',
                'The Q1 1701Q, due May 15, 2026, was filed and paid on June 15, 2026.',
            ],
            'ask' => 'Only the surcharge and interest are added. Should a compromise penalty be suggested too?',
            'figures' => function (TestCase $t) {
                TaxProfile::factory()->create();
                refSales(['2026-02-10' => 400_000]);

                return refFileOn($t, '1701Q', 2026, 1, CarbonImmutable::parse('2026-06-15'));
            },
            'expected' => [
                '1701Q, Q1 2026, filed June 15, 2026' => [
                    ['Sales/receipts, this quarter', 400000.0],
                    ['Sales/receipts, previous quarters', 0.0],
                    ['Less: allowable reduction from gross sales', 250000.0],
                    ['Taxable income to date', 150000.0],
                    ['Tax due (8%)', 12000.0],
                    ['Less: tax paid in previous quarters', 0.0],
                    ['Less: creditable tax withheld, previous quarters', 0.0],
                    ['Less: creditable tax withheld, this quarter', 0.0],
                    ['Tax payable', 12000.0],
                    ['Add: surcharge (10%)', 1200.0],
                    ['Add: interest (6% a year, 31 days)', 61.0],
                    ['Total amount payable', 13261.0],
                ],
            ],
        ],
        'R9' => [
            'title' => 'An employer with one employee',
            'facts' => [
                'The taxpayer hired one employee on October 1, 2026, at ₱30,000 a month, with no contributions or de minimis benefits entered.',
                'Her 13th month for October to December, ₱7,500, is paid with December\'s pay.',
                'Tax is withheld each month by the BIR withholding table, with the year-end adjustment in December.',
            ],
            'ask' => 'The November 1601-C withholds ₱1,375.05 by the table, yet lists the same ₱30,000 as taxable compensation not withheld on (item 23), because her pay for the year, ₱90,000, is under ₱250,000. Which is right for October and November, and should the December refund be shown this way?',
            'figures' => function (TestCase $t) {
                TaxProfile::factory()->create();
                $employee = Employee::query()->create([
                    'first_name' => 'Joy', 'last_name' => 'Ramos', 'position' => 'Virtual assistant',
                    'monthly_pay' => 30_000, 'started_on' => '2026-10-01',
                ]);
                $t->travelTo(CarbonImmutable::parse('2027-01-31 09:00'));
                $payroll = app(Payroll::class);
                $months = collect($payroll->year(2026))->keyBy('month');
                $year = $payroll->employeeYear($employee, 2026);
                $month = fn (array $m) => [
                    ['Total compensation', $m['compensation']],
                    ['Less: minimum wage pay', $m['minimum_wage']],
                    ['Less: 13th month and other benefits, untaxed', $m['benefits']],
                    ['Less: de minimis benefits', $m['de_minimis']],
                    ['Less: employee\'s contributions', $m['contributions']],
                    ['Taxable compensation', $m['taxable']],
                    ['Taxable compensation not withheld on', $m['not_withheld']],
                    ['Tax withheld', $m['withheld']],
                    ['Year-end refund to the employee', $m['refund']],
                ];

                return [
                    '1601-C, November 2026' => $month($months[11]),
                    '1601-C, December 2026' => $month($months[12]),
                    '2316 for 2026' => [
                        ['Total compensation, with the 13th month', $year['pay']],
                        ['13th month and other benefits', $year['benefits']],
                        ['Of which untaxed', $year['benefits_exempt']],
                        ['Taxable compensation', $year['taxable']],
                        ['Tax due for the year', $year['tax']],
                        ['Tax withheld before December', $year['withheld']],
                        ['Refunded in December', $year['refund']],
                    ],
                ];
            },
            'expected' => [
                '1601-C, November 2026' => [
                    ['Total compensation', 30000.0],
                    ['Less: minimum wage pay', 0.0],
                    ['Less: 13th month and other benefits, untaxed', 0.0],
                    ['Less: de minimis benefits', 0.0],
                    ['Less: employee\'s contributions', 0.0],
                    ['Taxable compensation', 30000.0],
                    ['Taxable compensation not withheld on', 30000.0],
                    ['Tax withheld', 1375.05],
                    ['Year-end refund to the employee', 0.0],
                ],
                '1601-C, December 2026' => [
                    ['Total compensation', 37500.0],
                    ['Less: minimum wage pay', 0.0],
                    ['Less: 13th month and other benefits, untaxed', 7500.0],
                    ['Less: de minimis benefits', 0.0],
                    ['Less: employee\'s contributions', 0.0],
                    ['Taxable compensation', 30000.0],
                    ['Taxable compensation not withheld on', 30000.0],
                    ['Tax withheld', 0.0],
                    ['Year-end refund to the employee', 2750.1],
                ],
                '2316 for 2026' => [
                    ['Total compensation, with the 13th month', 97500.0],
                    ['13th month and other benefits', 7500.0],
                    ['Of which untaxed', 7500.0],
                    ['Taxable compensation', 90000.0],
                    ['Tax due for the year', 0.0],
                    ['Tax withheld before December', 2750.1],
                    ['Refunded in December', 2750.1],
                ],
            ],
        ],
        'R10' => [
            'title' => 'A corporation on the 20% rate, compared with MCIT',
            'facts' => [
                'A domestic corporation registered with the BIR in 2022, so MCIT applies from 2026. Total assets of ₱30,000,000, excluding land.',
                'Each quarter of 2026: sales of ₱3,000,000 and cost of sales of ₱1,800,000. Deductions of ₱700,000, ₱650,000, ₱750,000 and ₱800,000; creditable tax withheld of ₱25,000, ₱25,000, ₱40,000 and ₱40,000.',
                'Q1 and Q2 were filed and paid on time.',
            ],
            'ask' => "The 1702Q applies the 20% rate to taxable income to date; the rule review's question Q6 asks whether that's how the quarterly return tests it.",
            'figures' => function (TestCase $t) {
                TaxProfile::factory()->create();
                $corporation = Corporation::query()->create([
                    'name' => 'Bayani Creative Studio Inc.', 'operations_began' => 2022, 'total_assets' => 30_000_000, 'deduction_method' => 'itemized',
                ]);

                foreach ([[1, 700_000, 25_000], [2, 650_000, 25_000], [3, 750_000, 40_000], [4, 800_000, 40_000]] as [$quarter, $deductions, $withheld]) {
                    $corporation->quarters()->create([
                        'year' => 2026, 'quarter' => $quarter, 'sales' => 3_000_000, 'cost_of_sales' => 1_800_000,
                        'other_income' => 0, 'deductions' => $deductions, 'withheld' => $withheld,
                    ]);
                }

                $tax = app(CorporateTax::class);
                $lines = [];

                foreach ([1, 2, 3] as $quarter) {
                    $due = CarbonImmutable::parse($tax->quarter($corporation, 2026, $quarter)['due_date']);
                    $t->travelTo($due->setTime(9, 0));
                    $q = $tax->quarter($corporation, 2026, $quarter);
                    $t->post("/corporation/2026/{$quarter}/filed", ['filed_on' => $due->toDateString(), 'amount_paid' => $q['payable']])->assertSessionHasNoErrors();
                    $lines["1702Q, Q{$quarter} 2026, filed {$due->format('F j, Y')}"] = [
                        ['Sales to date', $q['to_date']['sales']],
                        ['Less: cost of sales', $q['to_date']['cost_of_sales']],
                        ['Gross income to date', $q['to_date']['gross_income']],
                        ['Less: deductions', $q['to_date']['deductions']],
                        ['Taxable income to date', $q['to_date']['taxable_income']],
                        ['Regular tax ('.($q['rate'] * 100).'%)', $q['regular_tax']],
                        ['MCIT (2% of gross income to date)', $q['mcit']],
                        ['Tax due (the higher)', $q['tax_due']],
                        ['Less: tax paid in previous quarters', $q['paid_regular'] + $q['paid_mcit']],
                        ['Less: creditable tax withheld', $q['withheld_previous'] + $q['withheld_this_quarter']],
                        ['Tax payable', $q['payable']],
                    ];
                }

                $annual = $tax->annual($corporation, 2026);
                $lines['1702-RT for 2026'] = [
                    ['Sales', $annual['figures']['sales']],
                    ['Less: cost of sales', $annual['figures']['cost_of_sales']],
                    ['Gross income', $annual['figures']['gross_income']],
                    ['Less: deductions', $annual['figures']['deductions']],
                    ['Taxable income', $annual['net_taxable_income']],
                    ['Regular tax ('.($annual['rate'] * 100).'%)', $annual['regular_tax']],
                    ['MCIT (2% of gross income)', $annual['mcit']],
                    ['Tax due (the higher)', $annual['tax_due']],
                    ['Less: tax paid in Q1 to Q3', $annual['quarterly_payments']],
                    ['Less: creditable tax withheld for the year', $annual['withheld']],
                    ['Tax payable', $annual['payable']],
                ];

                return $lines;
            },
            'expected' => [
                '1702Q, Q1 2026, filed June 1, 2026' => [
                    ['Sales to date', 3000000.0],
                    ['Less: cost of sales', 1800000.0],
                    ['Gross income to date', 1200000.0],
                    ['Less: deductions', 700000.0],
                    ['Taxable income to date', 500000.0],
                    ['Regular tax (20%)', 100000.0],
                    ['MCIT (2% of gross income to date)', 24000.0],
                    ['Tax due (the higher)', 100000.0],
                    ['Less: tax paid in previous quarters', 0.0],
                    ['Less: creditable tax withheld', 25000.0],
                    ['Tax payable', 75000.0],
                ],
                '1702Q, Q2 2026, filed September 1, 2026' => [
                    ['Sales to date', 6000000.0],
                    ['Less: cost of sales', 3600000.0],
                    ['Gross income to date', 2400000.0],
                    ['Less: deductions', 1350000.0],
                    ['Taxable income to date', 1050000.0],
                    ['Regular tax (20%)', 210000.0],
                    ['MCIT (2% of gross income to date)', 48000.0],
                    ['Tax due (the higher)', 210000.0],
                    ['Less: tax paid in previous quarters', 75000.0],
                    ['Less: creditable tax withheld', 50000.0],
                    ['Tax payable', 85000.0],
                ],
                '1702Q, Q3 2026, filed December 1, 2026' => [
                    ['Sales to date', 9000000.0],
                    ['Less: cost of sales', 5400000.0],
                    ['Gross income to date', 3600000.0],
                    ['Less: deductions', 2100000.0],
                    ['Taxable income to date', 1500000.0],
                    ['Regular tax (20%)', 300000.0],
                    ['MCIT (2% of gross income to date)', 72000.0],
                    ['Tax due (the higher)', 300000.0],
                    ['Less: tax paid in previous quarters', 160000.0],
                    ['Less: creditable tax withheld', 90000.0],
                    ['Tax payable', 50000.0],
                ],
                '1702-RT for 2026' => [
                    ['Sales', 12000000.0],
                    ['Less: cost of sales', 7200000.0],
                    ['Gross income', 4800000.0],
                    ['Less: deductions', 2900000.0],
                    ['Taxable income', 1900000.0],
                    ['Regular tax (20%)', 380000.0],
                    ['MCIT (2% of gross income)', 96000.0],
                    ['Tax due (the higher)', 380000.0],
                    ['Less: tax paid in Q1 to Q3', 210000.0],
                    ['Less: creditable tax withheld for the year', 130000.0],
                    ['Tax payable', 40000.0],
                ],
            ],
        ],
    ];
}

/** Sales received on each date. */
function refSales(array $sales): void
{
    foreach ($sales as $date => $amount) {
        Transaction::factory()->on($date)->create(['counterparty' => 'Lumen Digital', 'amount' => $amount]);
    }
}

/** The annual return the app prepares for the year. */
function refAnnualForm(TaxProfile $profile, int $year): string
{
    return app(TaxCalculationService::class)->forYear($profile, $year)['quarters'][4]['form'];
}

/**
 * A return as prepared on its due date, filed and paid then.
 *
 * @return array<string, list<array{string, float}>>
 */
function refFileOnTime(TestCase $t, string $form, int $year, int $quarter): array
{
    $calendar = app(FilingCalendar::class);
    $due = match ($form) {
        '2551Q' => $calendar->percentageTaxDue($year, $quarter),
        '2550Q' => $calendar->vatDue($year, $quarter),
        default => $calendar->incomeTaxDue($year, $quarter),
    };

    return refFileOn($t, $form, $year, $quarter, $due);
}

/**
 * A return as prepared on a date, filed and paid in full then.
 *
 * @return array<string, list<array{string, float}>>
 */
function refFileOn(TestCase $t, string $form, int $year, int $quarter, CarbonImmutable $on): array
{
    $t->travelTo($on->setTime(9, 0));
    $forms = app(ReturnForm::class);
    $layout = $forms->layout($forms->snapshot(TaxProfile::current(), $year, $quarter, $form));

    $t->post('/filings', [
        'form' => $form, 'year' => $year, 'quarter' => $quarter, 'filed_on' => $on->toDateString(),
        'paid' => true, 'amount_paid' => $layout['total_payable'], 'paid_on' => $on->toDateString(),
    ])->assertSessionHasNoErrors();

    $period = $quarter === 4 && $form !== '2551Q' && $form !== '2550Q' ? "{$year} annual" : "Q{$quarter} {$year}";

    return ["{$form}, {$period}, filed {$on->format('F j, Y')}" => array_map(fn (array $line) => [$line['label'], $line['amount']], $layout['lines'])];
}

/**
 * The CPA's copy: each case's facts and returns, line by line, with a
 * column to tick or correct.
 */
function referenceCasesDocument(): string
{
    $cases = referenceCases();
    $lines = [
        '<!-- Generated from tests/Feature/Acceptance/ReferenceCasesTest.php. Change the cases there, then run: REFERENCE_CASES_WRITE=1 php artisan test --filter=ReferenceCases -->',
        '',
        '# Reference cases',
        '',
        'Worked returns from BuwisBuddy for a CPA to check, alongside the rule review in [RULE-REVIEW.md](RULE-REVIEW.md). Each case is a typical taxpayer: the facts that matter, then each return the app prepares for them, line by line, as its return page lists the amounts to copy into eBIRForms. Every return is prepared on its due date and paid in full that day, except in case R8.',
        '',
        "**What we ask:** for each line, tick the CPA column if the amount is right, or write the right amount and its basis. Note under the return any line that's missing or shouldn't be there, and answer the case's question where it has one.",
        '',
        'Once you confirm or correct the figures, the app is tested against them: until it computes a corrected figure, its tests fail.',
        '',
        '| Case | Taxpayer | Returns |',
        '| ---- | -------- | ------- |',
    ];

    foreach ($cases as $id => $case) {
        $forms = array_unique(array_map(fn (string $return) => strtok($return, ', '), array_keys($case['expected'])));
        $lines[] = "| {$id} | {$case['title']} | ".implode(', ', $forms).' |';
    }

    foreach ($cases as $id => $case) {
        array_push($lines, '', "## {$id} · {$case['title']}", '', ...array_map(fn (string $fact) => "- {$fact}", $case['facts']));

        if (isset($case['ask'])) {
            array_push($lines, '', "**Please check:** {$case['ask']}");
        }

        foreach ($case['expected'] as $return => $rows) {
            array_push($lines, '', "### {$return}", '', '| Line | Amount (₱) | CPA |', '| ---- | ---------: | --- |');

            foreach ($rows as [$label, $amount]) {
                $lines[] = "| {$label} | ".number_format($amount, 2).' |     |';
            }
        }
    }

    array_push(
        $lines,
        '',
        '---',
        '',
        '## Sign-off',
        '',
        '|                       |     |',
        '| --------------------- | --- |',
        '| Reviewer              |     |',
        '| CPA / PRC licence no. |     |',
        '| Date of review        |     |',
        '| Cases changed         |     |',
        '| Signature             |     |',
    );

    return implode("\n", $lines)."\n";
}

test('each reference case computes the figures expected of it', function (string $id) {
    $case = referenceCases()[$id];

    expect(($case['figures'])($this))->toBe($case['expected']);
})->with(fn () => array_keys(referenceCases()));

test('the CPA’s copy shows the cases as they stand', function () {
    $path = base_path('docs/REFERENCE-CASES.md');

    if (getenv('REFERENCE_CASES_WRITE')) {
        file_put_contents($path, referenceCasesDocument());
    }

    expect(file_get_contents($path))->toBe(referenceCasesDocument());
});
