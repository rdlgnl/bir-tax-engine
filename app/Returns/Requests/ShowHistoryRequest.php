<?php

namespace App\Returns\Requests;

use App\Returns\Services\ReturnForm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowHistoryRequest extends FormRequest
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
            'form' => ['nullable', Rule::in([...ReturnForm::INCOME_TAX_FORMS, '2551Q', '2550Q'])],
        ];
    }
}
