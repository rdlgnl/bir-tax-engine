/** A return laid out line by line, from App\Returns\Services\ReturnForm::layout(). */
export type ReturnLine = {
    label: string;
    amount: number;
    kind: 'amount' | 'subtract' | 'total';
};

export type ReturnLayout = {
    form: string;
    edition: string;
    title: string;
    year: number;
    quarter: number;
    period: string;
    method: string;
    taxpayer: {
        name: string;
        tin: string | null;
        rdo: string | null;
        atc: string;
    };
    lines: ReturnLine[];
    payable: number;
    overpayment: number;
    rule_pack: string;
    rules: string[];
    taken_at: string;
};

/** App\Returns\Services\ReturnHistory entries. */
export type HistoryEntry = {
    year: number;
    quarter: number;
    form: string;
    title: string;
    status: 'open' | 'due' | 'overdue' | 'filed';
    amount: number;
    due_date: string;
    filed_on: string | null;
    paid_on: string | null;
};

export type HistoryDetail = {
    entry: HistoryEntry;
    layout: ReturnLayout;
    filing: {
        filed_on: string;
        reference_number: string | null;
        amount_paid: number | null;
        paid_on: string | null;
        payment_channel: string | null;
        frozen: boolean;
    } | null;
    drift: { payable_now: number; difference: number } | null;
};

export type ReportYear = { year: number; sawt_quarters: number[] };
