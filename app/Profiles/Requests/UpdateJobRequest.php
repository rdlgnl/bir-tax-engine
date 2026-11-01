<?php

namespace App\Profiles\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employer_name' => ['required', 'string', 'max:120'],
            'monthly_salary' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
        ];
    }
}
