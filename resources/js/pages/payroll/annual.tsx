import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { MarkReportedForm } from '@/components/withholding/mark-reported-form';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate, monthLong } from '@/lib/format';
import { payroll } from '@/routes';
import { certificate } from '@/routes/payroll';
import { pdf } from '@/routes/payroll/annual';
import type { PayrollAnnual } from '@/types';

type Props = { annual: PayrollAnnual; edition: string };

export default function PayrollAnnualPage({ annual, edition }: Props) {
    const { taxpayer } = usePage().props;
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const { year, status } = annual;
    const open = status === 'open';
    const toCheck = [
        { key: 'listed', label: `Every ${year} employee is listed` },
        { key: 'given', label: 'Each employee got their 2316' },
    ];
    const ungiven = annual.employees.filter((e) => e.given_on === null);

    return (
        <>
            <Head title={`1604-C · ${year}`} />

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
                            Your filled 1604-C
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Official BIR form · {year} · employees
                            {open ? ' · draft until Dec 31' : ''}
                            {annual.filed_on
                                ? ` · filed ${formatDate(annual.filed_on, 'long')}`
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
                            href={pdf(year, { query: { print: 1 } }).url}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Printer className="size-4" aria-hidden />
                            Print
                        </a>
                    </Button>
                    <Button asChild className="h-11 rounded-xl font-bold">
                        <a href={pdf(year).url} download>
                            <Download className="size-4" aria-hidden />
                            Download PDF
                        </a>
                    </Button>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
                <PaperForm
                    form="1604-C"
                    title="Annual Information Return of Income Taxes Withheld on Compensation"
                    edition={edition}
                    parts={[
                        {
                            title: 'Part I · Background information',
                            split: true,
                            rows: [
                                { label: 'For the year', value: String(year) },
                                {
                                    label: 'Employer',
                                    value: (
                                        taxpayer?.full_name ?? ''
                                    ).toUpperCase(),
                                },
                            ],
                        },
                        {
                            title: 'Part II · Summary of remittances',
                            rows: [
                                ...annual.months.map((m) => ({
                                    label: monthLong(m.month),
                                    value:
                                        m.refund > 0
                                            ? `${formAmount(m.withheld)} (year-end adjustment)`
                                            : formAmount(m.withheld),
                                })),
                                ...(annual.refund > 0
                                    ? [
                                          {
                                              label: 'Refunded to employees',
                                              value: formAmount(annual.refund),
                                          },
                                      ]
                                    : []),
                                {
                                    label: `Net tax withheld for ${year}`,
                                    value: formAmount(
                                        annual.withheld - annual.refund,
                                    ),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    <Note
                        title={`File by ${formatDate(annual.due_date, 'full')}`}
                    >
                        No payment; it lists each employee and the 1601-C
                        remittances.
                    </Note>

                    {status !== 'filed' && (
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
                            <p className="mt-2 text-[13px] text-warning">
                                The employee list (alphalist) goes as a data
                                file with this return.
                            </p>
                        </SectionCard>
                    )}

                    <SectionCard title="Each employee's 2316">
                        <ul className="divide-y divide-divider">
                            {annual.employees.map((e) => (
                                <li key={e.id}>
                                    <Link
                                        href={certificate([e.id, year]).url}
                                        className="flex items-center justify-between gap-3 py-2.5 text-sm hover:underline"
                                    >
                                        <span className="font-bold">
                                            {e.name}
                                        </span>
                                        <span
                                            className={
                                                e.given_on
                                                    ? 'text-success'
                                                    : 'text-muted-foreground'
                                            }
                                        >
                                            {e.given_on
                                                ? 'Given'
                                                : `By ${formatDate(annual.due_date)}`}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </SectionCard>

                    {status === 'filed' ? (
                        <Note tone="success" title="1604-C filed">
                            Marked filed
                            {annual.filed_on
                                ? ` on ${formatDate(annual.filed_on, 'long')}`
                                : ''}
                            .
                        </Note>
                    ) : open ? (
                        <p className="text-sm text-muted-foreground">
                            File it after December's payroll
                            {ungiven.length > 0 &&
                                ', once each employee has their 2316'}
                            .
                        </p>
                    ) : (
                        <SectionCard>
                            <h2 className="mb-3 text-lg font-extrabold">
                                Filed it? Mark it filed
                            </h2>
                            <MarkReportedForm year={year} form="1604-C" />
                        </SectionCard>
                    )}
                </div>
            </div>
        </>
    );
}
