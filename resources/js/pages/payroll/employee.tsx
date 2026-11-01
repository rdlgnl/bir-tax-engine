import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, monthLong, peso, todayIso } from '@/lib/format';
import { payroll } from '@/routes';
import { compare } from '@/routes/tax';
import { annual, certificate, month as monthPage } from '@/routes/payroll';
import { destroy, store, update } from '@/routes/payroll/employees';
import type { Employee, EmployeeYear, RemittanceMonth } from '@/types';

type Props = {
    employee: Employee | null;
    payday: { withheld: number; net: number } | null;
    yearEnd?: EmployeeYear & {
        year: number;
        withheld_before: number;
        refund_by: string;
    };
    nextRemittance?: RemittanceMonth | null;
    annualDue?: string;
    regime?: string | null;
};

type EmployeeForm = {
    first_name: string;
    middle_name: string;
    last_name: string;
    tin: string;
    position: string;
    monthly_pay: string;
    started_on: string;
    ended_on: string;
};

export default function EmployeePage({
    employee,
    payday,
    yearEnd,
    nextRemittance,
    annualDue,
    regime,
}: Props) {
    const form = useForm<EmployeeForm>({
        first_name: employee?.first_name ?? '',
        middle_name: employee?.middle_name ?? '',
        last_name: employee?.last_name ?? '',
        tin: employee?.tin ?? '',
        position: employee?.position ?? '',
        monthly_pay: employee ? String(employee.monthly_pay) : '',
        started_on: employee?.started_on ?? todayIso(),
        ended_on: employee?.ended_on ?? '',
    });
    const first = employee?.first_name ?? 'them';
    const text = (
        name: 'first_name' | 'middle_name' | 'last_name' | 'tin' | 'position',
        label: string,
        optional = false,
    ) => (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={name} className="font-bold">
                {label}{' '}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                )}
            </Label>
            <Input
                id={name}
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                className="h-11 rounded-xl"
            />
            <FieldError message={form.errors[name]} />
        </div>
    );

    return (
        <>
            <Head title={employee ? employee.name : 'Add an employee'} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={payroll().url}
                    aria-label="Back to payroll"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        {employee ? 'Your employee' : 'Add an employee'}
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {employee
                            ? [
                                  employee.name,
                                  employee.position,
                                  `since ${formatDate(employee.started_on)}`,
                              ]
                                  .filter(Boolean)
                                  .join(' · ')
                            : 'Tax withheld on their pay, remitted on the 1601-C'}
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <form
                    noValidate
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit(employee ? update(employee.id) : store(), {
                            preserveScroll: true,
                        });
                    }}
                    className="flex flex-col gap-4"
                >
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="monthly_pay" className="font-bold">
                            Monthly taxable pay
                        </Label>
                        <MoneyInput
                            id="monthly_pay"
                            size="large"
                            value={form.data.monthly_pay}
                            onValueChange={(value) =>
                                form.setData('monthly_pay', value)
                            }
                            autoFocus={!employee}
                        />
                        {!form.errors.monthly_pay && (
                            <p className="text-[13px] text-muted-foreground">
                                After SSS, PhilHealth and Pag-IBIG shares.
                            </p>
                        )}
                        <FieldError message={form.errors.monthly_pay} />
                    </div>

                    {payday && employee && (
                        <SectionCard title="Each payday (monthly)">
                            <dl className="text-[15px] tabular-nums">
                                <div className="flex justify-between border-b border-divider py-3">
                                    <dt>Tax to withhold</dt>
                                    <dd className="font-bold">
                                        {peso(payday.withheld)}
                                    </dd>
                                </div>
                                <div className="flex justify-between border-b border-divider py-3">
                                    <dt>Pay {employee.first_name}</dt>
                                    <dd className="font-bold">
                                        {peso(payday.net)}
                                    </dd>
                                </div>
                                <div className="flex justify-between pt-3">
                                    <dt>Send to BIR</dt>
                                    <dd className="text-muted-foreground">
                                        {peso(payday.withheld)} by the 10th
                                    </dd>
                                </div>
                            </dl>
                        </SectionCard>
                    )}

                    <SectionCard title="Employee">
                        <div className="grid gap-4 sm:grid-cols-3">
                            {text('first_name', 'First name')}
                            {text('middle_name', 'Middle name', true)}
                            {text('last_name', 'Last name')}
                            {text('position', 'Position', true)}
                            {text('tin', 'TIN', true)}
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="started_on"
                                    className="font-bold"
                                >
                                    Started
                                </Label>
                                <Input
                                    id="started_on"
                                    type="date"
                                    value={form.data.started_on}
                                    onChange={(event) =>
                                        form.setData(
                                            'started_on',
                                            event.target.value,
                                        )
                                    }
                                    className="h-11 rounded-xl"
                                />
                                <FieldError message={form.errors.started_on} />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="ended_on" className="font-bold">
                                    Left{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (if they did)
                                    </span>
                                </Label>
                                <Input
                                    id="ended_on"
                                    type="date"
                                    value={form.data.ended_on}
                                    onChange={(event) =>
                                        form.setData(
                                            'ended_on',
                                            event.target.value,
                                        )
                                    }
                                    className="h-11 rounded-xl"
                                />
                                <FieldError message={form.errors.ended_on} />
                            </div>
                        </div>
                    </SectionCard>

                    {nextRemittance && annualDue && (
                        <SectionCard title="On your calendar">
                            <ul className="divide-y divide-divider">
                                <li className="flex items-center justify-between gap-3 py-3">
                                    <span>
                                        <span className="block text-[15px] font-bold">
                                            Remit{' '}
                                            {monthLong(nextRemittance.month)}{' '}
                                            withholding
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            Form 1601-C ·{' '}
                                            <Link
                                                href={
                                                    monthPage([
                                                        yearEnd?.year ?? 0,
                                                        nextRemittance.month,
                                                    ]).url
                                                }
                                                className="font-bold text-primary hover:underline"
                                            >
                                                Filled form
                                            </Link>
                                        </span>
                                    </span>
                                    <span className="font-bold">
                                        {formatDate(
                                            nextRemittance.due_date,
                                            'short',
                                        )}
                                    </span>
                                </li>
                                <li className="flex items-center justify-between gap-3 py-3">
                                    <span>
                                        <span className="block text-[15px] font-bold">
                                            Give {first} their 2316
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            Filled by the app from payroll ·{' '}
                                            <Link
                                                href={
                                                    certificate([
                                                        employee?.id ?? 0,
                                                        yearEnd?.year ?? 0,
                                                    ]).url
                                                }
                                                className="font-bold text-primary hover:underline"
                                            >
                                                Preview
                                            </Link>
                                        </span>
                                    </span>
                                    <span className="font-bold">
                                        By {formatDate(annualDue, 'long')}
                                    </span>
                                </li>
                                <li className="flex items-center justify-between gap-3 py-3">
                                    <span>
                                        <span className="block text-[15px] font-bold">
                                            Annual list of employees
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            Form 1604-C ·{' '}
                                            <Link
                                                href={
                                                    annual(yearEnd?.year ?? 0)
                                                        .url
                                                }
                                                className="font-bold text-primary hover:underline"
                                            >
                                                Filled form
                                            </Link>
                                        </span>
                                    </span>
                                    <span className="font-bold">
                                        {formatDate(annualDue, 'short')}
                                    </span>
                                </li>
                            </ul>
                        </SectionCard>
                    )}

                    <div className="flex flex-wrap gap-2.5">
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="h-12 rounded-xl px-6 text-[15px] font-bold"
                        >
                            Save payroll
                        </Button>
                        {employee && (
                            <Button
                                type="button"
                                variant="ghost"
                                className="h-12 rounded-xl text-destructive"
                                onClick={() => {
                                    if (
                                        window.confirm(
                                            `Remove ${employee.name} from payroll?`,
                                        )
                                    ) {
                                        router.delete(destroy(employee.id).url);
                                    }
                                }}
                            >
                                Remove
                            </Button>
                        )}
                    </div>
                </form>

                <div className="flex flex-col gap-4">
                    {yearEnd && employee && (
                        <Note
                            tone={
                                yearEnd.refund > 0 || yearEnd.tax === 0
                                    ? 'success'
                                    : 'info'
                            }
                            title="Year-end check"
                        >
                            {employee.first_name}'s {yearEnd.year} pay will
                            total {peso(yearEnd.pay)}
                            {yearEnd.tax === 0
                                ? ', below ₱250,000, so no tax is due on it.'
                                : `, so ${peso(yearEnd.tax)} in tax is due on it.`}{' '}
                            {yearEnd.refund > 0
                                ? `At the last payroll of the year nothing more is withheld, and the ${peso(yearEnd.refund)} withheld before is refunded to ${first} by ${formatDate(yearEnd.refund_by, 'long')}.`
                                : 'The last payroll of the year withholds whatever is still owed.'}
                        </Note>
                    )}
                    <Note title="Your own tax">
                        {regime === 'eight_percent'
                            ? "On 8%, salaries you pay don't lower your income tax. On graduated rates with itemized deductions, they would."
                            : 'On graduated rates with itemized deductions, salaries you pay are a deduction; record them as expenses.'}{' '}
                        <Link
                            href={compare().url}
                            className="font-bold underline"
                        >
                            Compare it
                        </Link>
                    </Note>
                    <Note tone="warning" title="Not covered">
                        SSS, PhilHealth and Pag-IBIG are separate agencies.
                        Track them elsewhere.
                    </Note>
                </div>
            </div>
        </>
    );
}
