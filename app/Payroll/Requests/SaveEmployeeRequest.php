<?php

namespace App\Payroll\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveEmployeeRequest extends FormRequest
{
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
            'started_on' => ['required', 'date', 'after_or_equal:2018-01-01'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
        ];
    }
}
