<?php

namespace App\Transactions\Requests;

use App\Transactions\Services\TransactionFilter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchTransactionsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['payment', 'invoice', 'expense', 'withheld'])],
            'period' => ['nullable', Rule::in(['quarter', 'year', 'custom'])],
            'year' => ['nullable', 'integer', 'between:2018,2100'],
            'from' => ['nullable', 'required_if:period,custom', 'date'],
            'to' => ['nullable', 'required_if:period,custom', 'date', 'after_or_equal:from'],
            'flag' => ['nullable', Rule::in(TransactionFilter::FLAGS)],
        ];
    }

    /**
     * The dates the period covers: this quarter, a year, or the dates given.
     *
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    public function range(CarbonImmutable $today): array
    {
        return match ($this->validated('period') ?? 'year') {
            'quarter' => [$today->firstOfQuarter(), $today->lastOfQuarter()],
            'custom' => [CarbonImmutable::parse($this->validated('from')), CarbonImmutable::parse($this->validated('to'))],
            default => [
                CarbonImmutable::create($this->integer('year', $today->year))->startOfYear(),
                CarbonImmutable::create($this->integer('year', $today->year))->endOfYear(),
            ],
        };
    }
}
