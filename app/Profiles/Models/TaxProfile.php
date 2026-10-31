<?php

namespace App\Profiles\Models;

use App\Books\Enums\BooksType;
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
 * @property BooksType|null $books_type
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
    'books_type',
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
            'books_type' => BooksType::class,
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
     * On 8%, percentage tax is replaced, but a COR that still lists it can
     * bring BIR letters about "unfiled" 2551Q returns. Form 1905 removes it.
     */
    public function corOutdated(): bool
    {
        return $this->regime === Regime::EightPercent && $this->registered_percentage_tax;
    }

    /**
     * Only purely self-employed people get the ₱250,000 reduction on 8%.
     */
    public function isPurelySelfEmployed(): bool
    {
        return $this->income_source !== IncomeSource::Both;
    }

    /**
     * The name in the parts the BIR forms ask for. Profiles from before the
     * name was asked in parts have only a full name, so it is split here:
     * the surname is the last word, or starts at a prefix like "Dela" or
     * "San"; everything before it is the first name. Getting the surname
     * right is what matters, since the forms print "LAST, FIRST MIDDLE".
     *
     * @return array{last: string|null, first: string|null, middle: string|null}
     */
    public function nameParts(): array
    {
        if (filled($this->last_name)) {
            return ['last' => $this->last_name, 'first' => $this->first_name, 'middle' => $this->middle_name];
        }

        $name = Str::squish((string) $this->full_name);

        if (str_contains($name, ',')) {
            [$last, $first] = array_map('trim', explode(',', $name, 2));

            return ['last' => $last ?: null, 'first' => $first ?: null, 'middle' => null];
        }

        $words = $name === '' ? [] : explode(' ', $name);
        $suffix = count($words) > 2 && preg_match('/^(jr|sr|i{2,3}|iv|v)\.?$/i', (string) end($words)) ? array_pop($words) : null;

        if (count($words) < 2) {
            return ['last' => null, 'first' => $name ?: null, 'middle' => null];
        }

        $prefixes = ['de', 'del', 'dela', 'della', 'delos', 'des', 'di', 'da', 'dos', 'san', 'sta', 'sta.', 'santa', 'sto', 'sto.', 'santo', 'van', 'von'];
        $start = count($words) - 1;

        for ($i = 1; $i < count($words) - 1; $i++) {
            if (in_array(Str::lower($words[$i]), $prefixes, true)) {
                $start = $i;
                break;
            }
        }

        return [
            'last' => implode(' ', array_slice($words, $start)),
            'first' => implode(' ', [...array_slice($words, 0, $start), ...($suffix ? [$suffix] : [])]),
            'middle' => null,
        ];
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
