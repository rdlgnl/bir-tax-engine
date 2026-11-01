<?php

namespace App\Transactions\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PayoutColumnsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'integer', 'min:0'],
            'sales' => ['required', 'integer', 'min:0', 'different:date'],
            'order' => ['nullable', 'integer', 'min:0'],
            'fees' => ['nullable', 'integer', 'min:0'],
            'withheld' => ['nullable', 'integer', 'min:0'],
            'payout' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
