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
    started_on: string;
    ended_on: string | null;
};

/** App\Payroll\Services\Payroll: one month of an employee's pay. */
export type PayMonth = {
    month: number;
    pay: number;
    withheld: number;
    refund: number;
    year_end: boolean;
};

export type EmployeeYear = {
    months: PayMonth[];
    pay: number;
    tax: number;
    withheld: number;
    refund: number;
};

/** A month's 1601-C: what was paid and withheld across employees. */
export type RemittanceMonth = {
    month: number;
    employees: number;
    compensation: number;
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
