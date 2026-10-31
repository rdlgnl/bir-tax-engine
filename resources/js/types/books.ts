export type BooksType = 'manual' | 'loose_leaf';

export type BooksPeriod = { year: number; month: number };

export type CashReceipt = {
    id: number;
    occurred_on: string;
    counterparty: string;
    invoice_number: string | null;
    amount: number;
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
};

export type JournalTotals = {
    amount: number;
    withheld: number;
    received: number;
    disbursed: number;
};
