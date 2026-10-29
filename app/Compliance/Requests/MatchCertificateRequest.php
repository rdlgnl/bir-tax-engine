<?php

namespace App\Compliance\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The half-filled 2307 form, sent while the person types, to show which
 * payments it covers. Nothing is stored.
 */
class MatchCertificateRequest extends FormRequest
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
            'year' => ['required', 'integer', 'between:2018,2100'],
            'quarter' => ['required', 'integer', 'between:1,4'],
            'tax_withheld' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
