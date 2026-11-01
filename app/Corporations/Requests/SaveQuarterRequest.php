<?php

namespace App\Corporations\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveQuarterRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $amount = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'];

        return [
            'sales' => $amount,
            'cost_of_sales' => $amount,
            'other_income' => $amount,
            'deductions' => $amount,
            'withheld' => $amount,
        ];
    }
}
