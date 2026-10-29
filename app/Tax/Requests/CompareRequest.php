<?php

namespace App\Tax\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompareRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'income' => ['nullable', 'numeric', 'min:0', 'max:50000000'],
            'expenses' => ['nullable', 'integer', 'between:0,100'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:50000000'],
        ];
    }
}
