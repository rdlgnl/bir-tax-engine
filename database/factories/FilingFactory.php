<?php

namespace Database\Factories;

use App\Compliance\Models\Filing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Filing>
 */
class FilingFactory extends Factory
{
    /**
     * Define the model's default state: Q1 2026 filed and paid.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => 2026,
            'quarter' => 1,
            'form' => '1701Q',
            'filed_on' => '2026-05-12',
            'amount_paid' => 0,
            'paid_on' => '2026-05-12',
            'payment_channel' => 'online',
        ];
    }

    public function quarter(int $quarter, string $filedOn, float $paid): static
    {
        return $this->state([
            'quarter' => $quarter,
            'filed_on' => $filedOn,
            'amount_paid' => $paid,
            'paid_on' => $filedOn,
        ]);
    }
}
