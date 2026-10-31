/** App\Tax\Services\VatCalculator, one quarter. */
export type VatQuarter = {
    quarter: number;
    vatable_sales: number;
    zero_rated_sales: number;
    exempt_sales: number;
    output_vat: number;
    purchases: number;
    input_vat: number;
    excluded_count: number;
    excluded_vat: number;
    carried_over: number;
    payable: number;
    excess: number;
    due_date: string;
    status: 'open' | 'due' | 'overdue' | 'filed';
    filed_on: string | null;
    amount_paid: number | null;
};
