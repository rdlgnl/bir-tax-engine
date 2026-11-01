<?php

namespace App\Profiles\Requests;

use App\Profiles\Enums\ClientsWithhold;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Enums\Profession;
use App\Profiles\Enums\SetupStep;
use App\Profiles\Enums\WorkType;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSetupStepRequest extends FormRequest
{
    /**
     * Get the validation rules for the step being answered.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match ($this->step()) {
            SetupStep::Income => [
                'income_source' => ['required', Rule::enum(IncomeSource::class)],
            ],
            SetupStep::Work => [
                'work_type' => ['required', Rule::enum(WorkType::class)],
                'profession' => ['nullable', Rule::enum(Profession::class)],
            ],
            SetupStep::Job => [
                'employer_name' => ['required', 'string', 'max:120'],
                'monthly_salary' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            ],
            SetupStep::About => [
                'first_name' => ['required', 'string', 'max:60'],
                'middle_name' => ['nullable', 'string', 'max:60'],
                'last_name' => ['required', 'string', 'max:60'],
                'birth_date' => ['nullable', 'date', 'before:today'],
                'email' => ['nullable', 'email', 'max:120'],
                'contact_number' => ['nullable', 'regex:/^[\d\s()+-]{7,20}$/'],
                'citizenship' => ['nullable', 'string', 'max:40'],
                'trade_name' => ['nullable', 'string', 'max:120'],
                'line_of_business' => ['nullable', 'string', 'max:120'],
                'city' => ['nullable', 'string', 'max:80'],
                'bir_registered' => ['required', 'boolean'],
            ],
            SetupStep::Certificate => $this->profile()->bir_registered ? [
                'tin' => ['required', 'regex:/^\d{3}-\d{3}-\d{3}-\d{3,5}$/'],
                'rdo_code' => ['required', 'digits:3'],
                'registered_address' => ['nullable', 'string', 'max:140'],
                'zip_code' => ['nullable', 'digits:4'],
                'registered_on' => ['nullable', 'date', 'before_or_equal:today'],
                'registered_income_tax' => ['required', 'boolean'],
                'registered_percentage_tax' => ['required', 'boolean'],
                'vat_registered' => ['required', 'boolean'],
                'withholding_agent' => ['required', 'boolean'],
            ] : [],
            SetupStep::Estimate => [
                'expected_income' => ['required', 'numeric', 'min:0', 'max:999999999'],
            ],
            SetupStep::Method => [
                'regime' => ['required', Rule::enum(Regime::class)],
            ],
            SetupStep::Expenses => [
                'deduction_method' => ['required', Rule::enum(DeductionMethod::class)],
                'expected_expense_percent' => ['required', 'integer', 'between:0,100'],
            ],
            SetupStep::Withholding => [
                'clients_withhold' => ['required', Rule::enum(ClientsWithhold::class)],
            ],
            SetupStep::Summary => [],
        };
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        if ($this->step() !== SetupStep::Method) {
            return [];
        }

        return [function (Validator $validator) {
            $profile = $this->profile();
            $regime = Regime::tryFrom((string) $this->input('regime'));
            $q1 = Filing::firstQuarterReturn(now()->year);

            if ($q1 !== null && $profile->regime !== null && $regime !== $profile->regime) {
                $validator->errors()->add('regime', "Your tax method for {$q1->year} is locked: you filed Q1 on {$q1->filed_on->format('F j')}.");
            }

            if ($regime === Regime::EightPercent && $profile->vat_registered) {
                $validator->errors()->add('regime', 'The 8% option is not available to VAT-registered taxpayers.');
            }

            if ($regime === Regime::EightPercent && (float) $profile->expected_income > config('tax.eight_percent.gross_limit')) {
                $validator->errors()->add('regime', 'The 8% option is only for sales up to ₱3,000,000 a year.');
            }
        }];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tin.regex' => 'Enter the TIN as printed on your COR, like 123-456-789-000.',
            'rdo_code.digits' => 'The RDO code is the 3-digit number on your COR.',
            'zip_code.digits' => 'The ZIP code is 4 digits.',
            'contact_number.regex' => 'Enter a landline or mobile number, like 0917 123 4567.',
            'expected_income.required' => 'A rough guess is fine.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->step() === SetupStep::Certificate && $this->filled('tin')) {
            $digits = preg_replace('/\D/', '', (string) $this->input('tin'));

            $this->merge([
                'tin' => strlen($digits) === 9 || strlen($digits) >= 12
                    ? implode('-', [...str_split(substr($digits, 0, 9), 3), substr($digits, 9) ?: '000'])
                    : $this->input('tin'),
            ]);
        }
    }

    public function step(): SetupStep
    {
        $step = $this->route('step');

        return $step instanceof SetupStep ? $step : SetupStep::from((string) $step);
    }

    public function profile(): TaxProfile
    {
        return TaxProfile::current() ?? new TaxProfile;
    }
}
