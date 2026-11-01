import type { Penalty } from './returns';

/** App\Loans\Controllers\LoanController: a loan and its documentary stamp tax. */
export type Loan = {
    id: number;
    signed_on: string;
    amount: number;
    lender: string;
    lender_tin: string | null;
    lender_kind: 'bank' | 'person';
    term_months: number;
    filed_on: string | null;
    reference_number: string | null;
    amount_paid: number | null;
    dst: {
        tax: number;
        due_date: string;
        status: 'bank' | 'due' | 'overdue' | 'filed';
        penalties: Penalty | null;
        total: number;
    };
};
