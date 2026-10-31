<?php

namespace App\Data\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDataSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'backup_folder' => ['sometimes', 'required', 'string', 'max:500', function (string $attribute, mixed $value, Closure $fail) {
                $folder = $this->folder();

                if (! str_starts_with($folder, '/') && ! preg_match('#^[A-Za-z]:[\\\\/]#', $folder)) {
                    $fail('Give the full path of the folder, like ~/Documents/BuwisBuddy.');
                } elseif (! is_dir($folder) && ! @mkdir($folder, 0755, true) && ! is_dir($folder)) {
                    $fail('That folder can’t be created. Pick another.');
                } elseif (! is_writable($folder)) {
                    $fail('BuwisBuddy can’t save into that folder. Pick another.');
                }
            }],
            'backup_reminder' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The folder as a full path, with ~ standing for the home folder.
     */
    public function folder(): string
    {
        $folder = rtrim(trim((string) $this->input('backup_folder')), '/\\');
        $home = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME')), '/');

        return $home !== '' && str_starts_with($folder, '~') ? $home.substr($folder, 1) : $folder;
    }
}
