<?php

namespace App\Profiles\Models;

use App\Profiles\Enums\ClientsWithhold;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Enums\Profession;
use App\Profiles\Enums\WorkType;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use Carbon\CarbonImmutable;
use Database\Factories\TaxProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The taxpayer using this device. The app keeps exactly one.
 *
 * @property int $id
 * @property IncomeSource|null $income_source
 * @property WorkType|null $work_type
 * @property Profession|null $profession
 * @property string|null $full_name
 * @property string|null $last_name
 * @property string|null $first_name
 * @property string|null $middle_name
 * @property CarbonImmutable|null $birth_date
 * @property string|null $email
 * @property string|null $contact_number
 * @property string|null $citizenship
 * @property string|null $trade_name
 * @property string|null $line_of_business
 * @property string|null $city
 * @property string|null $registered_address
 * @property string|null $zip_code
 * @property bool $bir_registered
 * @property string|null $tin
 * @property string|null $rdo_code
 * @property CarbonImmutable|null $registered_on
 * @property bool $registered_income_tax
 * @property bool $registered_percentage_tax
 * @property bool $vat_registered
 * @property bool $withholding_agent
 * @property string|null $expected_income
 * @property Regime|null $regime
 * @property DeductionMethod|null $deduction_method
 * @property int|null $expected_expense_percent
 * @property ClientsWithhold|null $clients_withhold
 * @property CarbonImmutable|null $setup_completed_at
 */
#[Fillable([
    'income_source',
    'work_type',
    'profession',
    'full_name',
    'last_name',
    'first_name',
    'middle_name',
    'birth_date',
    'email',
    'contact_number',
    'citizenship',
    'trade_name',
    'line_of_business',
    'city',
    'registered_address',
    'zip_code',
    'bir_registered',
    'tin',
    'rdo_code',
    'registered_on',
    'registered_income_tax',
    'registered_percentage_tax',
    'vat_registered',
    'withholding_agent',
    'expected_income',
    'regime',
    'deduction_method',
    'expected_expense_percent',
    'clients_withhold',
])]
#[UseFactory(TaxProfileFactory::class)]
class TaxProfile extends Model
{
    /** @use HasFactory<TaxProfileFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'income_source' => IncomeSource::class,
            'work_type' => WorkType::class,
            'profession' => Profession::class,
            'birth_date' => 'date',
            'bir_registered' => 'boolean',
            'registered_on' => 'date',
            'registered_income_tax' => 'boolean',
            'registered_percentage_tax' => 'boolean',
            'vat_registered' => 'boolean',
            'withholding_agent' => 'boolean',
            'expected_income' => 'decimal:2',
            'regime' => Regime::class,
            'deduction_method' => DeductionMethod::class,
            'expected_expense_percent' => 'integer',
            'clients_withhold' => ClientsWithhold::class,
            'setup_completed_at' => 'datetime',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->oldest('id')->first();
    }

    public function isComplete(): bool
    {
        return $this->setup_completed_at !== null;
    }

    /**
     * Only purely self-employed people get the ₱250,000 reduction on 8%.
     */
    public function isPurelySelfEmployed(): bool
    {
        return $this->income_source !== IncomeSource::Both;
    }

    public function firstName(): string
    {
        return $this->first_name ?: Str::of((string) $this->full_name)->trim()->before(' ')->value();
    }

    public function initials(): string
    {
        return Str::of((string) $this->full_name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }
}
