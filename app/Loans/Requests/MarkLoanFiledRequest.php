<?php

namespace App\Loans\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkLoanFiledRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filed_on' => ['required', 'date', 'before_or_equal:today'],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'amount_paid' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99'],
        ];
    }
}
