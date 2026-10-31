<?php

namespace App\Data\Requests;

use App\Data\Services\AppLock;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Turning the lock on, changing the PIN or the lock time. Changing an
 * existing lock asks for the current PIN first.
 */
class SetPinRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $lock = app(AppLock::class);

        return [
            'current_pin' => [$lock->enabled() && $this->routeIs('lock.settings') ? 'required' : 'nullable', 'string',
                function (string $attribute, mixed $value, Closure $fail) use ($lock) {
                    if ($lock->enabled() && ! $lock->check((string) $value)) {
                        $fail('That isn’t your current PIN.');
                    }
                }],
            'pin' => [$lock->enabled() ? 'nullable' : 'required', 'string', 'regex:/^\d{4,6}$/', 'confirmed'],
            'lock_after' => ['required', 'integer', Rule::in(config('lock.after_options'))],
            'reminders' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pin.required' => 'Choose a PIN of 4 to 6 digits.',
            'pin.regex' => 'Use 4 to 6 digits.',
            'pin.confirmed' => 'The two PINs don’t match.',
        ];
    }
}
