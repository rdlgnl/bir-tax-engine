<?php

namespace App\Letters\Requests;

use App\Tax\Services\TaxRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(array_keys((array) config('letters.kinds')))],
            'dated_on' => ['nullable', 'date', 'before_or_equal:today'],
            'office' => ['nullable', 'string', 'max:120'],
            'form' => ['nullable', Rule::in((array) config('letters.forms'))],
            'year' => ['nullable', 'integer', 'between:'.app(TaxRules::class)->firstDate()->year.',2100'],
            'quarter' => ['nullable', 'integer', 'between:1,4'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'reply_by' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
