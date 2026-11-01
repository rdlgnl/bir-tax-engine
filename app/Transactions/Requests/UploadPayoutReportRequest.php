<?php

namespace App\Transactions\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadPayoutReportRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'max:60'],
            'report' => ['required', 'file', 'extensions:csv,txt', 'max:10240'],
        ];
    }
}
