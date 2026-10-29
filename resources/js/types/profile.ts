import type { DeductionMethod, Regime } from './tax';

export type IncomeSource = 'employed' | 'business' | 'both';
export type WorkType = 'products' | 'services' | 'both';
export type Profession =
    | 'none'
    | 'cpa_lawyer'
    | 'doctor_dentist'
    | 'engineer_architect'
    | 'other';
export type ClientsWithhold = 'yes' | 'no' | 'unsure';

export type SetupStepKey =
    | 'income'
    | 'work'
    | 'about'
    | 'certificate'
    | 'estimate'
    | 'method'
    | 'expenses'
    | 'withholding'
    | 'summary';

export type SetupStep = {
    key: SetupStepKey;
    label: string;
    summary: string | null;
    answered: boolean;
};

export type SetupAnswers = {
    income_source: IncomeSource | null;
    work_type: WorkType | null;
    profession: Profession | null;
    full_name: string | null;
    trade_name: string | null;
    line_of_business: string | null;
    city: string | null;
    bir_registered: boolean;
    tin: string | null;
    rdo_code: string | null;
    registered_on: string | null;
    registered_income_tax: boolean;
    registered_percentage_tax: boolean;
    vat_registered: boolean;
    withholding_agent: boolean;
    expected_income: number | null;
    regime: Regime | null;
    deduction_method: DeductionMethod | null;
    expected_expense_percent: number | null;
    clients_withhold: ClientsWithhold | null;
};

/** App\Profiles\Resources\TaxProfileResource */
export type TaxProfileSummary = {
    full_name: string | null;
    first_name: string;
    initials: string;
    line_of_business: string | null;
    tin: string | null;
    rdo_code: string | null;
    regime: Regime | null;
    regime_label: string | null;
    classification: { label: string; source: string }[];
    taxes: { label: string; detail: string }[];
    registration: { label: string; status: string; note: string | null }[];
    todos: { label: string; detail: string | null }[];
};

export type Taxpayer = {
    first_name: string;
    full_name: string | null;
    initials: string;
};

export type NextDeadline = {
    date: string;
    form: string;
    quarter: number;
    amount: number | null;
};
