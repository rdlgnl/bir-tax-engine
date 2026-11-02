<?php

namespace App\Profiles\Models;

use App\Books\Enums\BooksType;
use App\Profiles\Enums\ClientsWithhold;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Enums\Profession;
use App\Profiles\Enums\WorkType;
use App\Returns\Models\Filing;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Factories\TaxProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

/**
 * The taxpayer using this device. The app keeps exactly one.
 *
 * @property int $id
 * @property IncomeSource|null $income_source
 * @property string|null $employer_name
 * @property string|null $monthly_salary
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
    'employer_name',
    'monthly_salary',
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

    /** The year a copy from forYear() stands for: it's read, never saved. */
    public ?int $yearView = null;

    protected static function booted(): void
    {
        static::saving(function (self $profile) {
            if ($profile->yearView !== null) {
                throw new LogicException("This is the profile as it stood for {$profile->yearView}; save the profile itself.");
            }
        });

        static::updating(fn (self $profile) => $profile->keepPastYears());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'income_source' => IncomeSource::class,
            'monthly_salary' => 'decimal:2',
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

    /**
     * The profile as it stood for a tax year: the method and registrations
     * the year was taxed on, where they differ from today's. Read-only.
     */
    public function forYear(int $year): self
    {
        $kept = TaxYear::query()->where('year', $year)->first();

        if ($kept === null) {
            return $this;
        }

        $copy = clone $this;
        $copy->yearView = $year;

        foreach (TaxYear::ELECTIONS as $field) {
            if ($kept->getRawOriginal($field) !== null) {
                $copy->setAttribute($field, $kept->getAttribute($field));
            }
        }

        return $copy;
    }

    /**
     * The profile once an 8% year's sales pass ₱3,000,000: graduated rates
     * for the rest of the year (RR 8-2018). The OSD is chosen on the Q1
     * return, which an 8% filer made for 8%, so expenses are itemized.
     * REVIEW: whether the OSD can still be chosen after the switch.
     * Read-only, like forYear().
     */
    public function onGraduatedRates(int $year): self
    {
        $copy = clone $this;
        $copy->yearView = $year;
        $copy->setAttribute('regime', Regime::Graduated);
        $copy->setAttribute('deduction_method', $this->deduction_method ?? DeductionMethod::Itemized);

        return $copy;
    }

    /**
     * Before the method or a registration changes, the years already
     * recorded keep the values they were taxed on; the change applies from
     * this year. (This year's method is locked once its Q1 return is filed.)
     */
    private function keepPastYears(): void
    {
        if (! $this->isDirty(TaxYear::ELECTIONS) || $this->getOriginal('regime') === null) {
            return;
        }

        $first = collect([
            Transaction::query()->min('occurred_on'),
            Filing::query()->min('filed_on'),
        ])->filter()->map(fn (string $date) => CarbonImmutable::parse($date)->year)->min();

        for ($year = $first ?? CarbonImmutable::now()->year; $year < CarbonImmutable::now()->year; $year++) {
            $kept = TaxYear::query()->firstOrNew(['year' => $year]);

            foreach (TaxYear::ELECTIONS as $field) {
                if ($kept->getRawOriginal($field) === null) {
                    $kept->setAttribute($field, $this->getOriginal($field));
                }
            }

            $kept->save();
        }
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
