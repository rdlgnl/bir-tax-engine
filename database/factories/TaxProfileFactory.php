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
        return [
            'income_source' => IncomeSource::Business,
            'work_type' => WorkType::Services,
            'profession' => Profession::None,
            'full_name' => fake()->name(),
            'line_of_business' => 'Freelance graphic design',
            'city' => 'Quezon City',
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
}
