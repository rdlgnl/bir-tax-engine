export type TransactionType = 'payment' | 'invoice' | 'expense';

export type ExpenseCategory =
    | 'internet'
    | 'rent'
    | 'equipment'
    | 'software'
    | 'transport'
    | 'person'
    | 'other';

export type CertificateStatus =
    | 'not_needed'
    | 'received'
    | 'pending'
    | 'missing';

/** App\Transactions\Resources\TransactionResource */
export type Transaction = {
    id: number;
    type: TransactionType;
    occurred_on: string;
    quarter: number;
    counterparty: string;
    amount: number;
    tax_withheld: number;
    vat_kind: VatKind | null;
    vat_amount: number;
    counterparty_tin: string | null;
    certificate_status: CertificateStatus;
    certificate_received_on: string | null;
    invoice_number: string | null;
    invoice_paid: boolean;
    category: ExpenseCategory | null;
    category_label: string | null;
    has_valid_invoice: boolean;
    description: string | null;
};

export type TransactionWithEffect = Transaction & { tax_effect: number };

/** What the add/edit form submits. Amounts are strings while being typed. */
export type TransactionForm = {
    type: TransactionType;
    occurred_on: string;
    counterparty: string;
    amount: string;
    tax_withheld: string;
    certificate_received_on: string | null;
    invoice_number: string;
    invoice_paid: boolean;
    category: ExpenseCategory | '';
    has_valid_invoice: boolean;
    description: string;
    vat_kind: VatKind;
    vat_amount: string;
    counterparty_tin: string;
};

/** App\Transactions\Controllers\TransactionPreviewController */
export type TransactionPreview = {
    quarter: number;
    tax_effect: number;
    credit: number;
    filed: boolean;
    duplicate: {
        counterparty: string;
        amount: number;
        occurred_on: string;
    } | null;
    unusual_multiple: number | null;
};

/** Shared as `transactionForm` by HandleInertiaRequests. */
export type VatKind = 'vatable' | 'zero_rated' | 'exempt';

export type TransactionFormContext = {
    regime: string | null;
    /** The VAT rate for a VAT-registered taxpayer, else null. */
    vatRate: number | null;
    expectedWithholdingPercent: number;
    recentCounterparties: string[];
    awaitingCertificates: Transaction[];
};
