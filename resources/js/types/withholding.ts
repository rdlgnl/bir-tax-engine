type Status = 'open' | 'due' | 'overdue' | 'filed';

/** App\Withholding\Services\WithholdingAgent: one month of a quarter. */
export type WithholdingMonth = {
    month: number;
    withheld: number;
    /** Null for the quarter's last month, which goes on the 1601-EQ. */
    due_date: string | null;
    status: Status;
    filed_on: string | null;
    amount_paid: number | null;
};

export type WithholdingPayee = {
    key: string;
    name: string;
    tin: string | null;
    atc: string;
    income: number;
    withheld: number;
    months: [number, number, number];
    given_on: string | null;
};

export type WithholdingAtcRow = {
    atc: string;
    label: string;
    rate: number;
    base: number;
    withheld: number;
};

export type WithholdingQuarter = {
    quarter: number;
    months: WithholdingMonth[];
    withheld: number;
    remitted: number;
    still_due: number;
    atcs: WithholdingAtcRow[];
    payees: WithholdingPayee[];
    due_date: string;
    status: Status;
    filed_on: string | null;
};

/** A payment the taxpayer withheld on, as listed on the 0619-E page. */
export type WithholdingPayment = {
    payee: string;
    atc: string;
    amount: number;
    withheld: number;
    date: string;
};

/** The taxpayer as the withholding agent named on the forms. */
export type WithholdingAgentIdentity = {
    name: string;
    tin: string | null;
    rdo: string | null;
    address: string | null;
};
