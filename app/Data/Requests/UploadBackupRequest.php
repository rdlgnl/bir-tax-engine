<?php

namespace App\Data\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadBackupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'backup' => ['required', 'file', 'extensions:'.config('backup.extension'), 'max:'.config('backup.max_upload_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'backup.extensions' => 'Pick a BuwisBuddy backup file (.bbk).',
        ];
    }
}
