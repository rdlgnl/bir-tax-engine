/** App\Corporations\Controllers\CorporationController */
export type Corporation = {
    id: number;
    name: string;
    tin: string | null;
    rdo_code: string | null;
    registered_address: string | null;
    zip_code: string | null;
    contact_number: string | null;
    email: string | null;
    operations_began: number;
    total_assets: number;
    deduction_method: 'itemized' | 'osd';
    vat_registered: boolean;
    withholding_agent: boolean;
    employees: number;
};

export type CorporateFigures = {
    sales: number;
    cost_of_sales: number;
    gross_from_operation: number;
    other_income: number;
    gross_income: number;
    deductions: number;
    taxable_income: number;
};

type CorporateTaxRate = {
    rate: number;
    small: boolean;
    regular_tax: number;
    mcit_applies: boolean;
    mcit: number;
    uses: 'regular' | 'mcit';
    tax_due: number;
};

/** App\Corporations\Services\CorporateTax::quarter() */
export type CorporateQuarterTax = CorporateTaxRate & {
    year: number;
    quarter: number;
    has_figures: boolean;
    this_quarter: CorporateFigures;
    to_date: CorporateFigures;
    previous_taxable_income: number;
    gross_by_quarter: number[];
    paid_regular: number;
    paid_mcit: number;
    withheld_previous: number;
    withheld_this_quarter: number;
    credits: number;
    payable: number;
    due_date: string;
    statutory_date: string;
    due_note: string;
    status: 'open' | 'due' | 'overdue' | 'filed';
    filed_on: string | null;
    reference_number: string | null;
    amount_paid: number | null;
};

/** App\Corporations\Services\CorporateTax::annual() */
export type CorporateAnnualEstimate = CorporateTaxRate & {
    year: number;
    estimated: boolean;
    quarters_entered: number;
    figures: CorporateFigures;
    withheld: number;
    withheld_previous: number;
    withheld_fourth_quarter: number;
    paid_regular: number;
    paid_mcit: number;
    quarterly_payments: number;
    credits: number;
    payable: number;
    due_date: string;
    status: 'open' | 'due' | 'overdue' | 'filed';
    filed_on: string | null;
    reference_number: string | null;
    amount_paid: number | null;
};

/** A quarter's figures as the books have them, for editing. */
export type CorporateQuarterFigures = {
    sales: string;
    cost_of_sales: string;
    other_income: string;
    deductions: string;
    withheld: string;
};
