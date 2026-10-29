<?php

namespace App\Transactions\Resources;

use App\Transactions\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'occurred_on' => $this->occurred_on->toDateString(),
            'quarter' => $this->quarter(),
            'counterparty' => $this->counterparty,
            'amount' => (float) $this->amount,
            'tax_withheld' => (float) $this->tax_withheld,
            'certificate_status' => $this->certificateStatus(now())->value,
            'certificate_received_on' => $this->certificate_received_on?->toDateString(),
            'invoice_number' => $this->invoice_number,
            'invoice_paid' => $this->invoice_paid,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'has_valid_invoice' => $this->has_valid_invoice,
            'description' => $this->description,
        ];
    }
}
