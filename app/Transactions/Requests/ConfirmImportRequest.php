<?php

namespace App\Transactions\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmImportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => ['array'],
            'answers.*.kind' => ['required', 'in:payment,personal'],
            'answers.*.client' => ['nullable', 'required_if:answers.*.kind,payment', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.*.client.required_if' => 'Who paid you?',
        ];
    }
}
