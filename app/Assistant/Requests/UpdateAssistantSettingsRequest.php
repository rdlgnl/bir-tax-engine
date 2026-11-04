<?php

namespace App\Assistant\Requests;

use App\Assistant\Services\AssistantSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAssistantSettingsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'provider' => ['required', Rule::in(AssistantSettings::PROVIDERS)],
            'model' => $this->input('provider') === 'claude'
                ? ['required', Rule::in(array_keys(AssistantSettings::CLAUDE_MODELS))]
                : ['required', 'string', 'max:120', 'regex:/^[\w.:\/@-]+$/'],
            'base_url' => ['exclude_unless:provider,openai', 'required', 'url:http,https', 'max:200'],
            'key' => ['nullable', 'string', 'max:500'],
            'remove_key' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $keyGiven = trim((string) $this->input('key')) !== '';
            $keyKept = AssistantSettings::current()->hasKey() && ! $this->boolean('remove_key');

            if ($this->boolean('enabled') && $this->input('provider') === 'claude' && ! $keyGiven && ! $keyKept) {
                $validator->errors()->add('key', 'Paste your Anthropic API key to turn the assistant on.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'model.required' => 'Name the model to use.',
            'model.regex' => 'The model name has characters it can\'t have.',
            'base_url.required' => 'Give the address of the API, like https://api.openai.com/v1.',
            'base_url.url' => 'Give the address of the API, like https://api.openai.com/v1.',
        ];
    }
}
