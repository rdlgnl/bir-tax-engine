<?php

namespace App\Transactions\Requests;

use App\Transactions\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The half-filled form, sent while the person types, to show what saving
 * it would do. Nothing is stored.
 */
class PreviewTransactionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'occurred_on' => ['required', 'date'],
            'counterparty' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'tax_withheld' => ['nullable', 'numeric', 'min:0'],
            'has_valid_invoice' => ['nullable', 'boolean'],
        ];
    }
}
