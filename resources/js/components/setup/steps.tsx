import type { InertiaFormProps } from '@inertiajs/react';
import { Info, Lock } from 'lucide-react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { OptionCards } from '@/components/setup/option-card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Slider } from '@/components/ui/slider';
import { formatDate, peso } from '@/lib/format';
import type {
    ClientsWithhold,
    Comparison,
    DeductionMethod,
    IncomeSource,
    Profession,
    Regime,
    SetupAnswers,
    WorkType,
} from '@/types';

export type StepProps = {
    form: InertiaFormProps<SetupAnswers>;
};

function Heading({ title, lead }: { title: string; lead?: React.ReactNode }) {
    return (
        <div className="mb-6">
            <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[32px]">
                {title}
            </h1>
            {lead && (
                <p className="mt-2 text-[17px] text-muted-foreground">{lead}</p>
            )}
        </div>
    );
}

function Note({
    children,
    tone = 'info',
}: {
    children: React.ReactNode;
    tone?: 'info' | 'lock';
}) {
    const Icon = tone === 'lock' ? Lock : Info;

    return (
        <p
            className={
                tone === 'lock'
                    ? 'mt-5 flex items-start gap-2.5 rounded-xl border border-warning-border bg-warning-soft px-4 py-3 text-sm text-warning'
                    : 'mt-5 flex items-start gap-2.5 text-[15px] text-muted-foreground'
            }
        >
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
            <span>{children}</span>
        </p>
    );
}

export function IncomeStep({ form }: StepProps) {
    return (
        <>
            <Heading
                title="How do you earn money?"
                lead="Pick the one closest to this year. You can change it later."
            />
            <OptionCards<IncomeSource>
                label="How do you earn money?"
                value={form.data.income_source}
                onChange={(value) => form.setData('income_source', value)}
                options={[
                    {
                        value: 'employed',
                        title: "I'm employed",
                        description: 'Salary from an employer only',
                    },
                    {
                        value: 'business',
                        title: 'Business or freelance',
                        description: 'Clients, customers, my own practice',
                    },
                    {
                        value: 'both',
                        title: 'Both',
                        description: 'A job plus a side business',
                    },
                ]}
            />
            <FieldError message={form.errors.income_source} />
            <Note>
                Salary is taxed by your employer. Business income has its own
                options, like the 8% flat tax. If you have both, the rules
                change slightly, and we handle that.
            </Note>
        </>
    );
}

export function WorkStep({ form }: StepProps) {
    return (
        <>
            <Heading
                title="What do you mainly do?"
                lead="This decides how your clients withhold tax and how sales are counted."
            />
            <OptionCards<WorkType>
                label="What do you mainly do?"
                value={form.data.work_type}
                onChange={(value) => form.setData('work_type', value)}
                options={[
                    {
                        value: 'products',
                        title: 'Sell products',
                        description: 'Online shop, store, reselling',
                    },
                    {
                        value: 'services',
                        title: 'Provide services',
                        description: 'Freelance, consulting, a profession',
                    },
                    {
                        value: 'both',
                        title: 'Both',
                        description: 'Products and services',
                    },
                ]}
            />
            <FieldError message={form.errors.work_type} />
            <div className="mt-6 flex flex-col gap-2.5">
                <span className="text-[15px] font-bold">
                    Licensed professional?{' '}
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                </span>
                <ChoiceChips<Profession>
                    label="Licensed professional?"
                    value={form.data.profession ?? 'none'}
                    onChange={(value) => form.setData('profession', value)}
                    options={[
                        { value: 'none', label: 'No' },
                        { value: 'cpa_lawyer', label: 'CPA / lawyer' },
                        { value: 'doctor_dentist', label: 'Doctor / dentist' },
                        {
                            value: 'engineer_architect',
                            label: 'Engineer / architect',
                        },
                        { value: 'other', label: 'Other' },
                    ]}
                />
            </div>
        </>
    );
}

function TextField({
    form,
    name,
    label,
    optional,
    ...props
}: StepProps & {
    name:
        | 'full_name'
        | 'trade_name'
        | 'line_of_business'
        | 'city'
        | 'tin'
        | 'rdo_code';
    label: string;
    optional?: boolean;
} & Omit<React.ComponentProps<'input'>, 'form' | 'name'>) {
    const error = form.errors[name];

    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={name} className="text-[15px] font-bold">
                {label}{' '}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                )}
            </Label>
            <Input
                id={name}
                value={form.data[name] ?? ''}
                onChange={(event) => form.setData(name, event.target.value)}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${name}-error` : undefined}
                className="h-12 rounded-xl text-[15px] md:text-[15px]"
                {...props}
            />
            <FieldError id={`${name}-error`} message={error} />
        </div>
    );
}

export function AboutStep({ form }: StepProps) {
    return (
        <>
            <Heading
                title="A bit about you"
                lead="We'll fill in the rest from your BIR certificate on the next step."
            />
            <div className="grid gap-4 sm:grid-cols-2">
                <TextField
                    form={form}
                    name="full_name"
                    label="Your full name"
                    autoComplete="name"
                />
                <TextField
                    form={form}
                    name="trade_name"
                    label="Business or trade name"
                    optional
                    autoComplete="organization"
                />
                <TextField
                    form={form}
                    name="line_of_business"
                    label="What do you do, in a few words?"
                    placeholder="Freelance graphic design"
                />
                <TextField
                    form={form}
                    name="city"
                    label="City where you work"
                    autoComplete="address-level2"
                />
            </div>
            {form.data.income_source !== 'employed' && (
                <div className="mt-6 flex flex-col gap-2.5">
                    <span className="text-[15px] font-bold">
                        Are you registered with the BIR?
                    </span>
                    <ChoiceChips
                        label="Are you registered with the BIR?"
                        variant="segmented"
                        value={form.data.bir_registered ? 'yes' : 'no'}
                        onChange={(value) =>
                            form.setData('bir_registered', value === 'yes')
                        }
                        options={[
                            { value: 'yes', label: 'Yes, I have a COR' },
                            { value: 'no', label: 'Not yet' },
                        ]}
                    />
                    {!form.data.bir_registered && (
                        <Note>
                            Not yet? You'll get a Form 1901 checklist, and we'll
                            keep your estimates ready.
                        </Note>
                    )}
                </div>
            )}
        </>
    );
}

const taxTypes = [
    {
        name: 'registered_income_tax',
        label: 'Income tax',
        forms: '1701Q, 1701A',
    },
    {
        name: 'registered_percentage_tax',
        label: 'Percentage tax',
        forms: '2551Q',
    },
    { name: 'vat_registered', label: 'VAT', forms: '2550Q' },
    { name: 'withholding_agent', label: 'Withholding agent', forms: '1601-EQ' },
] as const;

export function CertificateStep({ form }: StepProps) {
    if (!form.data.bir_registered) {
        return (
            <>
                <Heading
                    title="Registering with the BIR"
                    lead="You told us you're not registered yet. Here's what to bring to your RDO, or do online through ORUS."
                />
                <ul className="flex list-disc flex-col gap-2 pl-5 text-[15px]">
                    <li>
                        Form 1901, filled in (for self-employed individuals and
                        professionals)
                    </li>
                    <li>
                        A government ID, and your PSA birth certificate if you
                        have no TIN yet
                    </li>
                    <li>
                        Proof of your business address, such as a lease or
                        barangay certificate
                    </li>
                    <li>For professionals: your PRC ID or other licence</li>
                </ul>
                <Note>
                    We'll keep estimating your tax from what you record, so
                    you're ready when your COR arrives.
                </Note>
            </>
        );
    }

    return (
        <>
            <Heading
                title="Your BIR certificate (COR)"
                lead="Copy these from your Certificate of Registration (Form 2303). It tells us which taxes you're registered for."
            />
            <div className="grid gap-4 sm:grid-cols-3">
                <TextField
                    form={form}
                    name="tin"
                    label="TIN"
                    placeholder="123-456-789-000"
                    inputMode="numeric"
                />
                <TextField
                    form={form}
                    name="rdo_code"
                    label="RDO code"
                    placeholder="028"
                    inputMode="numeric"
                    maxLength={3}
                />
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="registered_on"
                        className="text-[15px] font-bold"
                    >
                        Registered on{' '}
                        <span className="font-normal text-muted-foreground">
                            (optional)
                        </span>
                    </Label>
                    <Input
                        id="registered_on"
                        type="date"
                        value={form.data.registered_on ?? ''}
                        onChange={(event) =>
                            form.setData(
                                'registered_on',
                                event.target.value || null,
                            )
                        }
                        className="h-12 rounded-xl"
                    />
                    <FieldError message={form.errors.registered_on} />
                </div>
            </div>
            <fieldset className="mt-6">
                <legend className="mb-2.5 text-[15px] font-bold">
                    The "Tax Types" table lists
                </legend>
                <div className="grid gap-2.5 sm:grid-cols-2">
                    {taxTypes.map((type) => (
                        <label
                            key={type.name}
                            className="flex items-center gap-3 rounded-xl border px-4 py-3"
                        >
                            <Checkbox
                                checked={form.data[type.name]}
                                onCheckedChange={(checked) =>
                                    form.setData(type.name, checked === true)
                                }
                            />
                            <span className="text-[15px] font-bold">
                                {type.label}
                            </span>
                            <span className="ml-auto text-[13px] text-muted-foreground">
                                {type.forms}
                            </span>
                        </label>
                    ))}
                </div>
            </fieldset>
            <Note>Stays on this computer. Nothing is uploaded.</Note>
        </>
    );
}

const brackets = [
    {
        value: 'low',
        title: 'Under ₱250,000',
        min: 0,
        max: 249_999,
        guess: 200_000,
    },
    {
        value: 'mid',
        title: '₱250,000 to ₱3,000,000',
        min: 250_000,
        max: 3_000_000,
        guess: 800_000,
    },
    {
        value: 'high',
        title: 'Over ₱3,000,000',
        min: 3_000_001,
        max: Infinity,
        guess: 3_500_000,
    },
] as const;

export function EstimateStep({ form, year }: StepProps & { year: number }) {
    const income = form.data.expected_income;
    const bracket =
        income === null
            ? null
            : (brackets.find((b) => income >= b.min && income <= b.max)
                  ?.value ?? null);

    return (
        <>
            <Heading
                title={`Roughly how much will you earn in ${year}?`}
                lead="Before expenses. A guess is fine: we only use it to check which options you qualify for."
            />
            <OptionCards
                label={`Roughly how much will you earn in ${year}?`}
                value={bracket}
                onChange={(value) =>
                    form.setData(
                        'expected_income',
                        brackets.find((b) => b.value === value)!.guess,
                    )
                }
                options={brackets.map((b) => ({
                    value: b.value,
                    title: b.title,
                }))}
            />
            <div className="mt-6 flex max-w-sm flex-col gap-1.5">
                <Label
                    htmlFor="expected_income"
                    className="text-[15px] font-bold"
                >
                    Closer estimate
                </Label>
                <MoneyInput
                    id="expected_income"
                    size="large"
                    value={income === null ? '' : String(income)}
                    onValueChange={(value) =>
                        form.setData(
                            'expected_income',
                            value === '' ? null : Number(value),
                        )
                    }
                />
                <FieldError message={form.errors.expected_income} />
            </div>
            <Note>
                Only used to check your options. Real numbers come from what you
                record.
            </Note>
        </>
    );
}

export function MethodStep({
    form,
    comparison,
    methodLockedOn,
}: StepProps & { comparison: Comparison; methodLockedOn: string | null }) {
    const eight = comparison.options.find((o) => o.key === 'eight_percent')!;
    const osd = comparison.options.find((o) => o.key === 'graduated_osd')!;
    const eightAllowed = eight.available && !form.data.vat_registered;

    return (
        <>
            <Heading
                title="How should we compute your income tax?"
                lead={
                    <>
                        Based on about{' '}
                        <strong className="text-foreground">
                            {peso(comparison.income)}
                        </strong>{' '}
                        this year.{' '}
                        {eightAllowed
                            ? 'You qualify for both options.'
                            : 'Only graduated rates are open to you.'}
                    </>
                }
            />
            <OptionCards<Regime>
                label="Income tax method"
                columns={2}
                showRadio
                value={form.data.regime}
                onChange={(value) => form.setData('regime', value)}
                options={[
                    {
                        value: 'eight_percent',
                        title: 'Flat 8%',
                        badge:
                            eightAllowed && comparison.best === 'eight_percent'
                                ? 'Recommended'
                                : undefined,
                        disabled: !eightAllowed,
                        children: (
                            <>
                                <span className="mt-2 text-[34px] font-extrabold">
                                    {eightAllowed ? peso(eight.total) : '—'}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {eightAllowed ? (
                                        <>
                                            8% of income above ₱250,000
                                            <br />
                                            No percentage tax to file
                                            <br />
                                            No expense receipts needed for tax
                                        </>
                                    ) : form.data.vat_registered ? (
                                        'Not available to VAT-registered taxpayers'
                                    ) : (
                                        'Only for sales up to ₱3,000,000'
                                    )}
                                </span>
                            </>
                        ),
                    },
                    {
                        value: 'graduated',
                        title: 'Graduated rates',
                        badge:
                            comparison.best !== 'eight_percent'
                                ? 'Recommended'
                                : undefined,
                        children: (
                            <>
                                <span className="mt-2 text-[34px] font-extrabold">
                                    {peso(osd.total)}
                                </span>
                                <span className="grid w-full grid-cols-[1fr_auto] gap-x-4 text-sm text-muted-foreground">
                                    <span>Income tax, flat 40% deduction</span>
                                    <span>{peso(osd.income_tax)}</span>
                                    <span>Percentage tax 3%</span>
                                    <span>{peso(osd.percentage_tax)}</span>
                                </span>
                                <span className="text-sm font-bold text-primary">
                                    Next: choose actual expenses instead, if
                                    they're high
                                </span>
                            </>
                        ),
                    },
                ]}
            />
            <FieldError message={form.errors.regime} />
            <Note tone="lock">
                {methodLockedOn ? (
                    <>
                        Your method is locked for the year: you filed Q1 on{' '}
                        {formatDate(methodLockedOn, 'long')}.
                    </>
                ) : (
                    <>
                        <strong>This locks for the year</strong> when you file
                        your first-quarter return (due May 15). Until then you
                        can switch anytime. Based on NIRC Sec. 24(A)(2)(b) and
                        RR 8-2018.
                    </>
                )}
            </Note>
        </>
    );
}

export function ExpensesStep({
    form,
    comparison,
    onExpensesChange,
}: StepProps & {
    comparison: Comparison;
    onExpensesChange: (percent: number) => void;
}) {
    const eight = comparison.options.find((o) => o.key === 'eight_percent')!;
    const osd = comparison.options.find((o) => o.key === 'graduated_osd')!;
    const itemized = comparison.options.find(
        (o) => o.key === 'graduated_itemized',
    )!;
    const percent =
        form.data.expected_expense_percent ?? comparison.expense_percent;
    const cheapestGraduated = Math.min(osd.total, itemized.total);

    return (
        <>
            <Heading
                title="Keep receipts, or take a flat 40% off?"
                lead="On graduated rates, expenses lower your tax. Count your real expenses, or use a flat 40% deduction."
            />
            <div className="mb-6 flex max-w-md flex-col gap-3">
                <div className="flex items-baseline justify-between">
                    <Label id="expense-label" className="text-[15px] font-bold">
                        Your expenses, roughly
                    </Label>
                    <span className="text-lg font-extrabold">
                        {percent}% · {peso(comparison.expenses)}
                    </span>
                </div>
                <Slider
                    aria-labelledby="expense-label"
                    min={0}
                    max={95}
                    step={5}
                    value={[percent]}
                    onValueChange={([value]) => {
                        form.setData('expected_expense_percent', value);
                        onExpensesChange(value);
                    }}
                />
            </div>
            <OptionCards<DeductionMethod>
                label="Deduction method"
                columns={2}
                showRadio
                value={form.data.deduction_method}
                onChange={(value) => form.setData('deduction_method', value)}
                options={[
                    {
                        value: 'osd',
                        title: 'Flat 40% deduction',
                        children: (
                            <>
                                <span className="mt-2 text-[30px] font-extrabold">
                                    {peso(osd.income_tax)}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    Taxable {peso(osd.taxable)}. No expense
                                    receipts needed for tax. BIR calls this OSD.
                                </span>
                            </>
                        ),
                    },
                    {
                        value: 'itemized',
                        title: 'Actual expenses',
                        children: (
                            <>
                                <span className="mt-2 text-[30px] font-extrabold">
                                    {peso(itemized.income_tax)}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    Taxable {peso(itemized.taxable)}. Every
                                    expense needs a valid invoice in your name.
                                    BIR calls this itemized.
                                </span>
                            </>
                        ),
                    },
                ]}
            />
            <FieldError
                message={
                    form.errors.deduction_method ??
                    form.errors.expected_expense_percent
                }
            />
            <Note>
                Plus percentage tax of {peso(osd.percentage_tax)} either way.
                {eight.available &&
                    eight.total < cheapestGraduated &&
                    ` At ${percent}% expenses, flat 8% (${peso(eight.total)}) is still ${peso(cheapestGraduated - eight.total)} lower.`}
            </Note>
        </>
    );
}

export function WithholdingStep({ form }: StepProps) {
    return (
        <>
            <Heading
                title="Do clients deduct tax before paying you?"
                lead="For example, they bill ₱20,000 but send ₱19,000. That ₱1,000 counts as tax you already paid."
            />
            <OptionCards<ClientsWithhold>
                label="Do clients deduct tax before paying you?"
                value={form.data.clients_withhold}
                onChange={(value) => form.setData('clients_withhold', value)}
                options={[
                    { value: 'yes', title: 'Yes, some of them' },
                    { value: 'no', title: 'No, paid in full' },
                    { value: 'unsure', title: "I'm not sure" },
                ]}
            />
            <FieldError message={form.errors.clients_withhold} />
            {form.data.clients_withhold !== 'no' && (
                <Note>
                    We'll track each client's 2307, credit what they withheld,
                    and remind you each January to send sworn declarations so
                    they withhold 5%, not 10%.
                </Note>
            )}
        </>
    );
}
