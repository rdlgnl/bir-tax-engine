<?php

namespace App\Transactions\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Which columns hold the date, the description and the money, when the
 * statement's own names weren't recognised.
 */
class StatementColumnsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'integer', 'min:0'],
            'description' => ['required', 'integer', 'min:0'],
            'amount' => ['nullable', 'integer', 'min:0', 'required_without_all:credit,debit'],
            'credit' => ['nullable', 'integer', 'min:0'],
            'debit' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required_without_all' => 'Choose the amount column, or the money in and money out columns.',
        ];
    }
}
