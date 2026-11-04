<?php

namespace App\Assistant\Requests;

use App\Assistant\Services\AssistantSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AskRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if (! AssistantSettings::current()->ready()) {
                $validator->errors()->add('question', 'Turn the assistant on and add your API key first, under Profile & settings, Your data.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Type a question first.',
            'question.max' => 'Keep the question under 2,000 characters.',
        ];
    }
}
