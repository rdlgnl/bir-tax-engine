<?php

namespace Database\Seeders;

use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Juan, the sample taxpayer from the approved screens: a freelance designer
 * on the 8% option with Q1 and Q2 2026 filed. Totals match the design
 * (Q3 due ₱14,000, ₱695,000 recorded for 2026).
 */
class DemoTaxpayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profile = TaxProfile::factory()->create([
            'full_name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'line_of_business' => 'Freelance graphic design',
            'city' => 'Quezon City',
        ]);

        $payments = [
            // Q1: ₱180,000, ₱3,000 withheld.
            ['2026-01-12', 'Studio Uno', 60_000],
            ['2026-02-09', 'Kape Collective', 40_000],
            ['2026-03-03', 'Lumen Digital', 60_000, 3_000, '2026-04-10'],
            ['2026-03-20', 'Bayani Foods', 20_000],
            // Q2: ₱220,000, ₱5,000 withheld.
            ['2026-04-08', 'Studio Uno', 70_000],
            ['2026-05-05', 'Lumen Digital', 60_000, 3_000, '2026-07-08'],
            ['2026-05-28', 'Kape Collective', 50_000],
            ['2026-06-18', 'Acme Co.', 40_000, 2_000, '2026-07-15'],
            // Q3: ₱200,000, ₱2,000 withheld, Acme's 2307 still missing.
            ['2026-07-08', 'Kape Collective', 45_000],
            ['2026-07-22', 'Lumen Digital', 20_000, 1_000, '2026-10-09'],
            ['2026-08-05', 'Bayani Foods', 35_000],
            ['2026-08-19', 'Acme Co.', 20_000, 1_000],
            ['2026-09-02', 'Studio Uno', 50_000],
            ['2026-09-23', 'Kape Collective', 30_000],
            // Q4 so far, with the invoice numbers the books screen shows.
            ['2026-10-06', 'Studio Uno', 25_000, 0, null, '0140'],
            ['2026-10-20', 'Lumen Digital', 30_000, 1_500, null, '0141'],
        ];

        foreach ($payments as $row) {
            [$date, $client, $amount] = $row;

            Transaction::create([
                'type' => TransactionType::Payment,
                'occurred_on' => $date,
                'counterparty' => $client,
                'amount' => $amount,
                'tax_withheld' => $row[3] ?? 0,
                'certificate_received_on' => $row[4] ?? null,
                'invoice_number' => $row[5] ?? null,
            ]);
        }

        Transaction::create([
            'type' => TransactionType::Expense,
            'occurred_on' => '2026-10-15',
            'counterparty' => 'Converge ICT',
            'amount' => 3_500,
            'category' => ExpenseCategory::Internet,
            'has_valid_invoice' => true,
        ]);

        Transaction::create([
            'type' => TransactionType::Invoice,
            'occurred_on' => '2026-10-26',
            'counterparty' => 'Studio Uno',
            'amount' => 40_000,
            'invoice_number' => '0142',
            'invoice_paid' => false,
        ]);

        // Each filing freezes its figures, as marking a return filed does.
        $returns = app(ReturnForm::class);

        foreach ([[1, '2026-05-12', 0], [2, '2026-08-14', 4_000]] as [$quarter, $filedOn, $paid]) {
            Filing::factory()->quarter($quarter, $filedOn, $paid)->create([
                'reference_number' => "EBIR-2026-Q{$quarter}",
                'snapshot' => $returns->snapshot($profile, 2026, $quarter, '1701Q', CarbonImmutable::parse($filedOn)),
            ]);
        }
    }
}
