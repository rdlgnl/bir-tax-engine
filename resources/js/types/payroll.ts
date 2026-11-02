/** App\Payroll\Controllers\PayrollController */
export type Employee = {
    id: number;
    name: string;
    last_name: string;
    first_name: string;
    middle_name: string | null;
    tin: string | null;
    position: string | null;
    monthly_pay: number;
    contributions: number;
    de_minimis: number;
    minimum_wage: boolean;
    started_on: string;
    ended_on: string | null;
    previous_pay: number | null;
    previous_withheld: number | null;
};

/** App\Payroll\Services\Payroll: one month of an employee's pay. */
export type PayMonth = {
    month: number;
    /** Basic pay. */
    pay: number;
    /** The 13th month, paid with the last month. */
    benefits: number;
    de_minimis: number;
    contributions: number;
    taxable: number;
    withheld: number;
    refund: number;
    year_end: boolean;
};

export type EmployeeYear = {
    months: PayMonth[];
    method: 'table' | 'cumulative';
    minimum_wage: boolean;
    /** Everything paid: basic pay, the 13th month and de minimis benefits. */
    pay: number;
    basic: number;
    benefits: number;
    benefits_exempt: number;
    de_minimis: number;
    contributions: number;
    taxable: number;
    previous_pay: number;
    previous_withheld: number;
    tax: number;
    withheld: number;
    refund: number;
};

/** A month's 1601-C: what was paid and withheld across employees. */
export type RemittanceMonth = {
    month: number;
    employees: number;
    compensation: number;
    minimum_wage: number;
    benefits: number;
    de_minimis: number;
    contributions: number;
    taxable: number;
    not_withheld: number;
    withheld: number;
    refund: number;
    due_date: string;
    status: 'open' | 'due' | 'overdue' | 'filed';
    filed_on: string | null;
};

/** App\Payroll\Services\Payroll::annual, for the 1604-C. */
export type PayrollAnnual = {
    year: number;
    months: RemittanceMonth[];
    employees: {
        id: number;
        name: string;
        tin: string | null;
        pay: number;
        taxable: number;
        tax: number;
        withheld: number;
        refund: number;
        given_on: string | null;
    }[];
    compensation: number;
    withheld: number;
    refund: number;
    due_date: string;
    status: RemittanceMonth['status'];
    filed_on: string | null;
};
