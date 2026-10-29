<?php

namespace App\Transactions\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTransactionsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'between:2018,2100'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'type' => ['nullable', Rule::in(['payment', 'invoice', 'expense', 'withheld'])],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
