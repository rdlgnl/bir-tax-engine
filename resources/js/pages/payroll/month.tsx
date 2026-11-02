import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { MarkFiledForm } from '@/components/mark-filed-form';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate, monthLong, peso } from '@/lib/format';
import { payroll } from '@/routes';
import { pdf } from '@/routes/payroll/month';
import type { Penalty, RemittanceMonth } from '@/types';

type Props = {
    year: number;
    remittance: RemittanceMonth;
    penalties: Penalty | null;
    edition: string;
    employees: { name: string; tin: string | null }[];
};

export default function PayrollMonth({
    year,
    remittance,
    penalties,
    edition,
    employees,
}: Props) {
    const { taxpayer } = usePage().props;
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const name = monthLong(remittance.month);
    const args: [number, number] = [year, remittance.month];
    const total = remittance.withheld + (penalties?.total ?? 0);
    const missingTin = employees.filter((e) => !e.tin).map((e) => e.name);
    const toCheck = [
        { key: 'final', label: `${name} payroll is final` },
        {
            key: 'tins',
            label:
                employees.length === 1
                    ? `${employees[0].name}'s TIN is on file`
                    : "Each employee's TIN is on file",
        },
    ];

    return (
        <>
            <Head title={`1601-C · ${name} ${year}`} />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={payroll(year).url}
                        aria-label="Back to payroll"
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            Your filled 1601-C
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Official BIR form · {name} {year} · withholding on
                            salaries
                            {remittance.filed_on
                                ? ` · remitted ${formatDate(remittance.filed_on, 'long')}`
                                : ' · filled on this computer'}
                        </p>
                    </div>
                </div>
                <div className="flex gap-2.5">
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 rounded-xl font-bold"
                    >
                        <a
                            href={pdf(args, { query: { print: 1 } }).url}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Printer className="size-4" aria-hidden />
                            Print
                        </a>
                    </Button>
                    <Button asChild className="h-11 rounded-xl font-bold">
                        <a href={pdf(args).url} download>
                            <Download className="size-4" aria-hidden />
                            Download PDF
                        </a>
                    </Button>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
                <PaperForm
                    form="1601-C"
                    title="Monthly Remittance Return of Income Taxes Withheld on Compensation"
                    edition={edition}
                    parts={[
                        {
                            title: 'Part I · Background information',
                            split: true,
                            rows: [
                                {
                                    label: 'For the month',
                                    value: `${name} ${year}`,
                                },
                                {
                                    label: 'Withholding agent',
                                    value: (
                                        taxpayer?.full_name ?? ''
                                    ).toUpperCase(),
                                },
                                { label: 'Category', value: 'Private' },
                                {
                                    label: 'Employees',
                                    value: String(remittance.employees),
                                },
                            ],
                        },
                        {
                            title: 'Part II · Computation of tax',
                            rows: [
                                {
                                    label: 'Total compensation',
                                    value: formAmount(remittance.compensation),
                                },
                                {
                                    label: 'Less: non-taxable compensation',
                                    value: formAmount(
                                        remittance.compensation -
                                            remittance.taxable,
                                    ),
                                },
                                {
                                    label: 'Taxable compensation',
                                    value: formAmount(remittance.taxable),
                                },
                                ...(remittance.not_withheld > 0
                                    ? [
                                          {
                                              label: 'Less: pay of ₱250,000 or less a year',
                                              value: formAmount(
                                                  remittance.not_withheld,
                                              ),
                                          },
                                      ]
                                    : []),
                                {
                                    label: 'Tax withheld',
                                    value: formAmount(remittance.withheld),
                                },
                                ...(penalties
                                    ? [
                                          {
                                              label: 'Penalties',
                                              value: formAmount(
                                                  penalties.total,
                                              ),
                                          },
                                      ]
                                    : []),
                                {
                                    label: 'Tax to remit',
                                    value: formAmount(total),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    <Note
                        tone={
                            remittance.status === 'overdue' ? 'danger' : 'info'
                        }
                        title={`Remit by ${formatDate(remittance.due_date, 'full')}`}
                    >
                        The 10th of the next month for manual and eBIRForms
                        filers.
                        {penalties &&
                            ` Paying today adds ${peso(penalties.total)} in surcharge and interest.`}
                    </Note>

                    {remittance.status !== 'filed' && (
                        <SectionCard>
                            <h2 className="mb-3 text-lg font-extrabold">
                                Check before you sign
                            </h2>
                            {toCheck.map((check) => (
                                <label
                                    key={check.key}
                                    className="flex items-center gap-3 py-1.5 text-[15px]"
                                >
                                    <Checkbox
                                        checked={checks[check.key] ?? false}
                                        onCheckedChange={(c) =>
                                            setChecks({
                                                ...checks,
                                                [check.key]: c === true,
                                            })
                                        }
                                    />
                                    {check.label}
                                </label>
                            ))}
                            {remittance.refund > 0 && (
                                <p className="mt-2 text-[13px] text-warning">
                                    This month has the year-end adjustment:{' '}
                                    {peso(remittance.refund)} withheld before is
                                    refunded by January 25.
                                </p>
                            )}
                            {missingTin.length > 0 && (
                                <p className="mt-2 text-[13px] text-warning">
                                    No TIN yet for {missingTin.join(', ')}.
                                </p>
                            )}
                        </SectionCard>
                    )}

                    {remittance.status === 'filed' ? (
                        <Note tone="success" title="Remitted">
                            Marked remitted
                            {remittance.filed_on
                                ? ` on ${formatDate(remittance.filed_on, 'long')}`
                                : ''}
                            .
                        </Note>
                    ) : remittance.status === 'open' ? (
                        <p className="text-sm text-muted-foreground">
                            {name} isn't over yet. Remit after its last payday.
                        </p>
                    ) : (
                        <SectionCard>
                            <h2 className="mb-3 text-lg font-extrabold">
                                Remitted it? Mark it paid
                            </h2>
                            <MarkFiledForm
                                year={year}
                                quarter={Math.ceil(remittance.month / 3)}
                                month={remittance.month}
                                form="1601-C"
                                amountDue={total}
                            />
                        </SectionCard>
                    )}
                </div>
            </div>
        </>
    );
}
