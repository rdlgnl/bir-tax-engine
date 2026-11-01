<?php

namespace App\Compliance\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompensationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employer_name' => ['required', 'string', 'max:120'],
            'employer_tin' => ['nullable', 'regex:/^\d{3}-?\d{3}-?\d{3}(-?\d{3,5})?$/'],
            'gross_compensation' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999.99'],
            'non_taxable_compensation' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'lte:gross_compensation'],
            'tax_withheld' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'lte:gross_compensation'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
