<?php

namespace App\Compliance\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveHolidayRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2018-01-01', 'before_or_equal:2099-12-31'],
            'name' => ['required', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Pick the day.',
            'date.after_or_equal' => 'The app covers 2018 onwards.',
            'name.required' => 'Name the holiday, as the proclamation does.',
        ];
    }
}
