<?php

namespace App\Compliance\Requests;

use App\Compliance\Models\Filing;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFilingPaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount_paid' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'payment_channel' => ['nullable', Rule::in(['online', 'bank', 'bir_office'])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $filing = $this->route('filing');

            if ($filing instanceof Filing && $filing->isPaid()) {
                $validator->errors()->add('amount_paid', 'This return is already marked as paid.');
            }
        }];
    }
}
