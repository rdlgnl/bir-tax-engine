<?php

namespace App\Profiles\Requests;

use App\Tax\Enums\TaxpayerClassification;
use App\Tax\Services\TaxRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassificationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:'.app(TaxRules::class)->firstDate()->year.','.(now()->year + 1)],
            'classification' => ['nullable', Rule::enum(TaxpayerClassification::class)],
        ];
    }
}
