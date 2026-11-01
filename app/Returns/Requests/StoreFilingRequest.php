<?php

namespace App\Returns\Requests;

use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFilingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:2018,2100'],
            'quarter' => ['required', 'integer', 'between:1,4'],
            'form' => [
                'required',
                Rule::in([...ReturnForm::INCOME_TAX_FORMS, '2551Q', '2550Q', '0619-E', '1601-EQ', '1604-E', '1601-C']),
                // An amended return is filed beside the original.
                ...($this->boolean('amendment') ? [] : [
                    Rule::unique('filings')->where('year', $this->integer('year'))->where('quarter', $this->integer('quarter'))
                        ->where('month', $this->integer('month'))->where('amendment', 0),
                ]),
                // One income tax return per period, whichever annual form it was filed on.
                function (string $attribute, mixed $value, Closure $fail) {
                    $income = in_array($value, ReturnForm::INCOME_TAX_FORMS, true) && ! $this->boolean('amendment');

                    if ($income && Filing::query()->where('year', $this->integer('year'))->where('quarter', $this->integer('quarter'))
                        ->whereIn('form', ReturnForm::INCOME_TAX_FORMS)->exists()) {
                        $fail('This period’s income tax return is already marked filed.');
                    }
                },
            ],
            // Only the monthly 0619-E has a month, the first or second of its quarter.
            // Monthly forms: the 0619-E for the first or second month of a quarter, the 1601-C for any.
            'month' => ['exclude_unless:form,0619-E,1601-C', 'required', 'integer', 'between:1,12', function (string $attribute, mixed $value, Closure $fail) {
                if ((int) ceil((int) $value / 3) !== $this->integer('quarter')) {
                    $fail('The month is outside the quarter.');
                } elseif ($this->input('form') === '0619-E' && (int) $value % 3 === 0) {
                    $fail('The 0619-E is for the first or second month of the quarter.');
                }
            }],
            'amendment' => ['sometimes', 'boolean'],
            'filed_on' => ['required', 'date', 'before_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'paid' => ['required', 'boolean'],
            'amount_paid' => ['exclude_unless:paid,true', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99'],
            'paid_on' => ['exclude_unless:paid,true', 'required', 'date', 'before_or_equal:today'],
            'payment_channel' => ['exclude_unless:paid,true', 'nullable', Rule::in(['online', 'bank', 'bir_office'])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->hasAny(['year', 'quarter', 'month'])) {
                return;
            }

            // A monthly form can be filed once its month is over; the rest once their quarter is.
            $monthly = in_array($this->input('form'), ['0619-E', '1601-C'], true);
            $periodEnd = CarbonImmutable::create($this->integer('year'), $monthly ? $this->integer('month') : $this->integer('quarter') * 3)->endOfMonth();

            if (now()->lessThanOrEqualTo($periodEnd)) {
                $validator->errors()->add('quarter', $monthly
                    ? 'This month hasn\'t ended yet, so it can\'t be filed.'
                    : 'This quarter hasn\'t ended yet, so it can\'t be filed.');
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
            'form.unique' => 'This return is already marked as filed.',
            'amount_paid.required' => 'Enter what you paid, even if it was ₱0.',
        ];
    }
}
