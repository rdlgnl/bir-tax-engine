<?php

namespace App\Payroll\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveEmployeeRequest extends FormRequest
{
    /**
     * Blank amounts are none.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'contributions' => $this->input('contributions') ?: 0,
            'de_minimis' => $this->input('de_minimis') ?: 0,
            'minimum_wage' => $this->boolean('minimum_wage'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:60'],
            'first_name' => ['required', 'string', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'tin' => ['nullable', 'regex:/^\d{3}-?\d{3}-?\d{3}(-?\d{3,5})?$/'],
            'position' => ['nullable', 'string', 'max:80'],
            'monthly_pay' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999'],
            'contributions' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'lt:monthly_pay'],
            'de_minimis' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'minimum_wage' => ['boolean'],
            'started_on' => ['required', 'date', 'after_or_equal:2018-01-01'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'previous_pay' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999'],
            'previous_withheld' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'lte:previous_pay'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contributions.lt' => 'Their share of contributions should be less than their pay.',
            'previous_withheld.lte' => 'Tax withheld can’t be more than the pay it was withheld from.',
        ];
    }
}
