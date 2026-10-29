<?php

namespace App\Compliance\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCertificateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payor_name' => ['required', 'string', 'max:120'],
            'payor_tin' => ['nullable', 'regex:/^\d{3}-?\d{3}-?\d{3}(-?\d{3,5})?$/'],
            'year' => ['required', 'integer', 'between:2018,2100'],
            'quarter' => ['required', 'integer', 'between:1,4'],
            'amount_paid' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999.99'],
            'tax_withheld' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'lte:amount_paid'],
            'atc_code' => ['nullable', 'alpha_num', 'max:10'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payor_tin.regex' => 'Copy the TIN as printed, like 008-123-456-000.',
            'tax_withheld.lte' => 'Tax withheld can’t be more than the amount paid.',
            'file.mimes' => 'Use a PDF or a photo (JPG, PNG or WebP).',
            'file.max' => 'The file is over 10 MB. Try a smaller scan or photo.',
        ];
    }
}
