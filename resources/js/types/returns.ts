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
    regime: 'eight_percent' | 'graduated';
    deduction_method: 'osd' | 'itemized' | null;
    taxpayer: {
        name: string;
        tin: string | null;
        rdo: string | null;
        atc: string;
    };
    /** The taxpayer also has a job. */
    with_salary: boolean;
    lines: ReturnLine[];
    payable: number;
    /** Surcharge and interest for a return past its due date, else null. */
    penalties: Penalty | null;
    /** The tax payable plus any penalties. */
    total_payable: number;
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
    /** A filed 1701Q that changed since and needs amending. */
    amendment: { extra: number; overpaid: number } | null;
    /** Amended returns filed for this one. */
    amended: { number: number; filed_on: string; amount_paid: number | null }[];
};

export type ReportYear = { year: number; sawt_quarters: number[] };

export type SawtRow = {
    seq: number;
    payor: string;
    tin: string | null;
    atc: string | null;
    income: number;
    rate: number;
    withheld: number;
    certificate: boolean;
};

/** App\Tax\Services\Penalties */
export type Penalty = {
    surcharge: number;
    surcharge_rate: number;
    interest: number;
    interest_rate: number;
    days: number;
    compromise: number;
    total: number;
    due_date: string;
    pay_on: string;
};
