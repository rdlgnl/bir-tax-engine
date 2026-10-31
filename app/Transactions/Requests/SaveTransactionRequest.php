<?php

namespace App\Transactions\Requests;

use App\Compliance\Rules\NotInFiledQuarter;
use App\Returns\Models\Filing;
use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveTransactionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'occurred_on' => ['required', 'date', 'before_or_equal:today', new NotInFiledQuarter],
            'counterparty' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999.99'],
            'tax_withheld' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'lt:amount'],
            'certificate_received_on' => ['nullable', 'date', 'before_or_equal:today'],
            'invoice_number' => ['required_if:type,invoice', 'nullable', 'string', 'max:40'],
            'invoice_paid' => ['boolean'],
            'category' => ['required_if:type,expense', 'nullable', Rule::enum(ExpenseCategory::class)],
            'has_valid_invoice' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $existing = $this->route('transaction');

            // Moving an entry out of a filed quarter changes that quarter too.
            if ($existing instanceof Transaction && Filing::incomeTaxReturnCovering($existing->occurred_on) !== null) {
                $validator->errors()->add('occurred_on', NotInFiledQuarter::message($existing->occurred_on));
            }
        }];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Enter the full amount before anything was deducted.',
            'amount.gt' => 'Enter the full amount before anything was deducted.',
            'tax_withheld.lt' => 'Tax withheld must be less than the amount billed.',
            'occurred_on.before_or_equal' => 'Record things once they have happened.',
            'counterparty.required' => 'Who was it from or to?',
            'invoice_number.required_if' => 'Copy the number printed on the invoice.',
            'category.required_if' => 'Pick what it was for.',
        ];
    }

    /**
     * Clear the fields that don't apply to the chosen type, so switching an
     * entry from one type to another leaves nothing stale behind.
     */
    protected function prepareForValidation(): void
    {
        $type = TransactionType::tryFrom((string) $this->input('type'));

        $this->merge([
            'tax_withheld' => $type === TransactionType::Expense ? 0 : ($this->input('tax_withheld') ?: 0),
            'certificate_received_on' => $type === TransactionType::Expense ? null : $this->input('certificate_received_on'),
            // Every type keeps a number: the invoice issued, or the supplier's receipt for an expense.
            'invoice_number' => $this->input('invoice_number') ?: null,
            'invoice_paid' => $type === TransactionType::Invoice && $this->boolean('invoice_paid'),
            'category' => $type === TransactionType::Expense ? $this->input('category') : null,
            'has_valid_invoice' => $type === TransactionType::Expense && $this->boolean('has_valid_invoice'),
        ]);
    }
}
