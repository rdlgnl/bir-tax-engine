<?php

namespace App\Loans\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLoanRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'signed_on' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:2018-01-01'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999.99'],
            'lender' => ['required', 'string', 'max:120'],
            'lender_tin' => ['nullable', 'regex:/^\d{3}-?\d{3}-?\d{3}(-?\d{3,5})?$/'],
            'lender_kind' => ['required', Rule::in(['bank', 'person'])],
            'term_months' => ['required', 'integer', 'between:1,600'],
        ];
    }
}
