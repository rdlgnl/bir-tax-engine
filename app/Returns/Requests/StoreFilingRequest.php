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
                Rule::in([...ReturnForm::INCOME_TAX_FORMS, '2551Q']),
                Rule::unique('filings')->where('year', $this->integer('year'))->where('quarter', $this->integer('quarter')),
                // One income tax return per period, whichever annual form it was filed on.
                function (string $attribute, mixed $value, Closure $fail) {
                    $income = in_array($value, ReturnForm::INCOME_TAX_FORMS, true);

                    if ($income && Filing::query()->where('year', $this->integer('year'))->where('quarter', $this->integer('quarter'))
                        ->whereIn('form', ReturnForm::INCOME_TAX_FORMS)->exists()) {
                        $fail('This period’s income tax return is already marked filed.');
                    }
                },
            ],
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
            if ($validator->errors()->hasAny(['year', 'quarter'])) {
                return;
            }

            $periodEnd = CarbonImmutable::create($this->integer('year'), $this->integer('quarter') * 3)->endOfMonth();

            if (now()->lessThanOrEqualTo($periodEnd)) {
                $validator->errors()->add('quarter', 'This quarter hasn\'t ended yet, so it can\'t be filed.');
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
