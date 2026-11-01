export type BooksType = 'manual' | 'loose_leaf';

export type BooksPeriod = { year: number; month: number };

export type CashReceipt = {
    id: number;
    occurred_on: string;
    counterparty: string;
    invoice_number: string | null;
    amount: number;
    vat: number;
    withheld: number;
    received: number;
};

export type CashDisbursement = {
    id: number;
    occurred_on: string;
    counterparty: string;
    receipt_number: string | null;
    category: string | null;
    amount: number;
    vat: number;
    withheld: number;
    paid: number;
};

export type JournalTotals = {
    amount: number;
    vat: number;
    withheld: number;
    received: number;
    disbursed: number;
    disbursed_vat: number;
    disbursed_withheld: number;
    paid: number;
};

/** App\Books\Services\GeneralLedger */
export type JournalLine = { account: number; debit: number; credit: number };

export type JournalEntry = {
    ref: string;
    date: string;
    description: string;
    lines: JournalLine[];
};

export type LedgerAccount = {
    code: number;
    name: string;
    opening: number;
    debits: number;
    credits: number;
    closing: number;
    postings: {
        ref: string;
        date: string;
        description: string;
        debit: number;
        credit: number;
        balance: number;
    }[];
};

export type Ledger = {
    accounts: LedgerAccount[];
    debits: number;
    credits: number;
};
