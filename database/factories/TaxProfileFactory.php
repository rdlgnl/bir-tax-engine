<?php

namespace Database\Factories;

use App\Profiles\Enums\ClientsWithhold;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Enums\Profession;
use App\Profiles\Enums\WorkType;
use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A completed profile for a non-VAT freelancer on the 8% option.
 *
 * @extends Factory<TaxProfile>
 */
class TaxProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'income_source' => IncomeSource::Business,
            'work_type' => WorkType::Services,
            'profession' => Profession::None,
            'full_name' => "{$first} {$last}",
            'first_name' => $first,
            'last_name' => $last,
            'birth_date' => '1994-06-15',
            'email' => fake()->safeEmail(),
            'contact_number' => '09171234567',
            'citizenship' => 'FILIPINO',
            'line_of_business' => 'Freelance graphic design',
            'city' => 'Quezon City',
            'registered_address' => '12 Mabini St., Brgy. Malaya, Quezon City',
            'zip_code' => '1101',
            'bir_registered' => true,
            'tin' => '123-456-789-000',
            'rdo_code' => '028',
            'registered_on' => '2024-03-04',
            'registered_income_tax' => true,
            'registered_percentage_tax' => true,
            'vat_registered' => false,
            'withholding_agent' => false,
            'expected_income' => 800_000,
            'regime' => Regime::EightPercent,
            'deduction_method' => null,
            'clients_withhold' => ClientsWithhold::Yes,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TaxProfile $profile) {
            $profile->setup_completed_at ??= now();
        });
    }

    public function incomplete(): static
    {
        return $this->afterMaking(function (TaxProfile $profile) {
            $profile->setup_completed_at = null;
        });
    }

    public function graduated(DeductionMethod $method = DeductionMethod::Osd): static
    {
        return $this->state([
            'regime' => Regime::Graduated,
            'deduction_method' => $method,
        ]);
    }

    public function withSalary(): static
    {
        return $this->state(['income_source' => IncomeSource::Both]);
    }

    /**
     * A job alongside the business, with the employer and salary recorded.
     */
    public function withJob(float $monthlySalary = 50_000, string $employer = 'Northwind BPO Inc.'): static
    {
        return $this->state([
            'income_source' => IncomeSource::Both,
            'employer_name' => $employer,
            'monthly_salary' => $monthlySalary,
        ]);
    }
}
