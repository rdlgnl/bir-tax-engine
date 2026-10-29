<?php

namespace Database\Factories;

use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state: a payment received in full.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => TransactionType::Payment,
            'occurred_on' => fake()->dateTimeBetween('2026-01-01', '2026-09-30')->format('Y-m-d'),
            'counterparty' => fake()->company(),
            'amount' => fake()->numberBetween(5, 60) * 1000,
            'tax_withheld' => 0,
        ];
    }

    public function on(string $date): static
    {
        return $this->state(['occurred_on' => $date]);
    }

    public function withheld(float $amount, ?string $certificateReceivedOn = null): static
    {
        return $this->state([
            'tax_withheld' => $amount,
            'certificate_received_on' => $certificateReceivedOn,
        ]);
    }

    public function invoice(bool $paid = false): static
    {
        return $this->state([
            'type' => TransactionType::Invoice,
            'invoice_number' => (string) fake()->unique()->numberBetween(100, 9999),
            'invoice_paid' => $paid,
        ]);
    }

    public function expense(bool $validInvoice = false): static
    {
        return $this->state([
            'type' => TransactionType::Expense,
            'category' => ExpenseCategory::Internet,
            'has_valid_invoice' => $validInvoice,
        ]);
    }
}
