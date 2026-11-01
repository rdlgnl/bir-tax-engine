import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Plus } from 'lucide-react';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, monthLong, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import { month as monthPage } from '@/routes/payroll';
import { create } from '@/routes/payroll/employees';
import { employee as employeePage } from '@/routes/payroll';
import type { Employee, EmployeeYear, RemittanceMonth } from '@/types';

type Props = {
    year: number;
    employees: (Employee & { year: EmployeeYear })[];
    months: RemittanceMonth[];
};

const pill: Record<RemittanceMonth['status'], string> = {
    filed: 'bg-success-soft text-success',
    due: 'bg-warning-soft text-warning',
    overdue: 'bg-red-50 text-destructive',
    open: 'bg-muted text-muted-foreground',
};

export default function Payroll({ year, employees, months }: Props) {
    return (
        <>
            <Head title={`Payroll · ${year}`} />

            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Payroll
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {year} · tax withheld on the salaries you pay
                    </p>
                </div>
                <Button asChild className="h-11 rounded-xl px-5 font-bold">
                    <Link href={create().url}>
                        <Plus className="size-5" aria-hidden />
                        Add an employee
                    </Link>
                </Button>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-2">
                <SectionCard title="Employees">
                    {employees.length === 0 ? (
                        <p className="py-2 text-sm text-muted-foreground">
                            No one on payroll yet.
                        </p>
                    ) : (
                        <ul className="divide-y divide-divider">
                            {employees.map((employee) => (
                                <li key={employee.id}>
                                    <Link
                                        href={employeePage(employee.id).url}
                                        className="flex items-center justify-between gap-3 py-3 hover:bg-muted/50"
                                    >
                                        <span>
                                            <span className="block text-[15px] font-bold">
                                                {employee.name}
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                {peso(employee.monthly_pay)} a
                                                month ·{' '}
                                                {peso(employee.year.withheld)}{' '}
                                                withheld in {year}
                                            </span>
                                        </span>
                                        <ChevronRight
                                            className="size-4 text-muted-foreground"
                                            aria-hidden
                                        />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </SectionCard>

                <SectionCard title="1601-C each month">
                    {months.length === 0 ? (
                        <p className="py-2 text-sm text-muted-foreground">
                            Nothing to remit in {year}.
                        </p>
                    ) : (
                        <ul className="divide-y divide-divider">
                            {months.map((m) => (
                                <li key={m.month}>
                                    <Link
                                        href={monthPage([year, m.month]).url}
                                        className="flex items-center justify-between gap-3 py-3 hover:bg-muted/50"
                                    >
                                        <span>
                                            <span className="block text-[15px] font-bold">
                                                {monthLong(m.month)}
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                {peso(m.withheld)} withheld
                                                {m.refund > 0 &&
                                                    ` · ${peso(m.refund)} refunded`}
                                            </span>
                                        </span>
                                        <span className="flex items-center gap-2">
                                            <span
                                                className={cn(
                                                    'rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap',
                                                    pill[m.status],
                                                )}
                                            >
                                                {m.status === 'filed'
                                                    ? 'Remitted'
                                                    : m.status === 'open'
                                                      ? 'This month'
                                                      : `${m.status === 'overdue' ? 'Overdue' : 'Due'} ${formatDate(m.due_date)}`}
                                            </span>
                                            <ChevronRight
                                                className="size-4 text-muted-foreground"
                                                aria-hidden
                                            />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </SectionCard>
            </div>
        </>
    );
}
