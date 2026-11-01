export type Regime = 'eight_percent' | 'graduated';
export type DeductionMethod = 'osd' | 'itemized';

export type QuarterStatus = 'open' | 'due' | 'overdue' | 'filed';

/** One return, as App\Tax\Services\TaxCalculationService computes it. Quarter 4 is the annual return. */
export type QuarterResult = {
    quarter: number;
    form: string;
    quarter_gross: number;
    quarter_withheld: number;
    gross: number;
    deductible_expenses: number;
    exempt_amount: number;
    deduction: number;
    taxable: number;
    income_tax: number;
    prior_payments: number;
    withheld: number;
    withheld_unconfirmed: number;
    due: number;
    excess_credit: number;
    percentage_tax: number | null;
    statutory_date: string;
    due_date: string;
    due_date_moved_reason: string | null;
    days_left: number;
    status: QuarterStatus;
    filed_on: string | null;
    amount_paid: number | null;
    paid_on: string | null;
};

export type YearStatus = 'ok' | 'cannot_compute' | 'not_applicable';

export type YearResult = {
    year: number;
    regime: Regime | null;
    deduction_method: DeductionMethod | null;
    status: YearStatus;
    gross: number;
    quarters: Record<1 | 2 | 3 | 4, QuarterResult>;
};

export type CompareOptionKey =
    | 'eight_percent'
    | 'graduated_osd'
    | 'graduated_itemized';

export type CompareOption = {
    key: CompareOptionKey;
    available: boolean;
    exempt_amount: number;
    taxable: number;
    income_tax: number;
    percentage_tax: number;
    total: number;
};

export type Comparison = {
    income: number;
    expense_percent: number;
    expenses: number;
    salary: number | null;
    over_limit: boolean;
    options: CompareOption[];
    best: CompareOptionKey | null;
    savings: number;
};

export type AppliedRule = {
    id: string;
    version: number;
    title: string;
    basis: string;
    effective: string;
    detail: string | null;
};

export type Explanation = {
    reasons: { text: string; source: string; applies: boolean }[];
    rules: AppliedRule[];
    skipped: { id: string; title: string; reason: string }[];
    warnings: string[];
};

export type RulePack = { version: string; reviewed: boolean };

export type NextPayment = {
    state:
        | 'empty'
        | 'cannot_compute'
        | 'due'
        | 'overdue'
        | 'clear'
        | 'not_applicable';
    year: number;
    quarter: QuarterResult | null;
    estimate: number | null;
    next_deadline: string | null;
    days_until_next: number | null;
};

export type ChecklistItem = {
    key: string;
    label: string;
    done: boolean;
    state: 'done' | 'warning' | 'todo';
    detail: string;
    action: 'transactions' | 'tax' | 'file' | 'pay' | null;
};

export type FilingSummary = {
    id: number;
    filed_on: string;
    amount_paid: number | null;
    paid_on: string | null;
};

export type Checklist = {
    items: ChecklistItem[];
    done: number;
    total: number;
    missing_certificates: { counterparty: string; amount: number }[];
    filing: FilingSummary | null;
};

/** A deadline from App\Compliance\Services\TaxObligations::deadlines(). */
export type Deadline = {
    date: string;
    statutory_date: string;
    title: string;
    kind:
        | 'income_tax'
        | 'percentage_tax'
        | 'vat'
        | 'withholding'
        | 'reminder'
        | 'decision';
    status: 'open' | 'due' | 'overdue' | 'filed' | 'reminder';
    amount: number | null;
    note: string | null;
    tone: 'default' | 'action';
    /** Where to act on it, when the app has a page for that. */
    href: string | null;
};

export type CertificateRow = {
    counterparty: string;
    quarter: number;
    amount: number;
    amount_paid: number;
    payment_date: string | null;
    status: 'received' | 'missing' | 'pending';
    received_on: string | null;
    expected_by: string;
    certificate_id: number | null;
    transaction_id: number;
};

export type CertificateTracker = {
    credits: number;
    confirmed: number;
    waiting: number;
    rows: CertificateRow[];
    filed: {
        quarters: number[];
        count: number;
        amount: number;
        all_received: boolean;
    } | null;
};
