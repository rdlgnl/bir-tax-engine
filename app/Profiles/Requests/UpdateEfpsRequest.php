<?php

namespace App\Profiles\Requests;

use App\Tax\Enums\EfpsGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEfpsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'efps_group' => ['nullable', Rule::enum(EfpsGroup::class)],
            'efps_from' => ['nullable', 'date_format:Y-m'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'efps_from.date_format' => 'Choose the month, like March 2026.',
        ];
    }
}
