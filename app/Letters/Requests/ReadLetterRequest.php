<?php

namespace App\Letters\Requests;

use App\Assistant\Services\AssistantSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReadLetterRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if (! AssistantSettings::current()->ready()) {
                $validator->errors()->add('file', 'Turn the assistant on and add your API key first, under Profile & settings, Your data.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Add a photo or PDF of the letter first.',
            'file.mimes' => 'Add the letter as a PDF, JPG, PNG or WebP.',
            'file.max' => 'The file is over 10 MB. Take a smaller photo of it.',
            'consent.accepted' => 'Tick the box to send the letter to the assistant.',
        ];
    }
}
