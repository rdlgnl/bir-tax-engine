<?php

namespace App\Corporations\Requests;

use App\Tax\Enums\EfpsGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCorporationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'tin' => ['nullable', 'regex:/^\d{3}-?\d{3}-?\d{3}(-?\d{3,5})?$/'],
            'rdo_code' => ['nullable', 'regex:/^\d{3}$/'],
            'registered_address' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'regex:/^\d{4}$/'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'operations_began' => ['required', 'integer', 'between:1900,'.now()->year],
            'total_assets' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'],
            'deduction_method' => ['required', Rule::in(['itemized', 'osd'])],
            'vat_registered' => ['boolean'],
            'withholding_agent' => ['boolean'],
            'employees' => ['required', 'integer', 'between:0,10000'],
            'efps_group' => ['nullable', Rule::enum(EfpsGroup::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tin.regex' => 'Enter the TIN as 000-000-000.',
            'rdo_code.regex' => 'The RDO code is three digits, like 044.',
            'zip_code.regex' => 'The ZIP code is four digits.',
        ];
    }
}
