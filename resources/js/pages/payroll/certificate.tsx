import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate, monthLong } from '@/lib/format';
import { annual, employee as employeePage } from '@/routes/payroll';
import { given, pdf } from '@/routes/payroll/certificate';
import type { Employee, EmployeeYear } from '@/types';

type Props = {
    employee: Employee;
    year: number;
    pay: EmployeeYear;
    givenOn: string | null;
    dueDate: string;
    refundBy: string;
    edition: string;
};

export default function EmployeeCertificate({
    employee,
    year,
    pay,
    givenOn,
    dueDate,
    refundBy,
    edition,
}: Props) {
    const { taxpayer } = usePage().props;
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const [saving, setSaving] = useState(false);
    const args: [number, number] = [employee.id, year];
    const first = pay.months[0]?.month ?? 1;
    const last = pay.months[pay.months.length - 1]?.month ?? 12;
    const before = pay.months.filter((m) => !m.year_end);
    const withheldBefore = before.reduce((sum, m) => sum + m.withheld, 0);
    const short = (month: number) => monthLong(month).slice(0, 3);
    const toCheck = [
        {
            key: 'identity',
            label: `${employee.first_name}'s TIN and name match their ID`,
        },
        {
            key: 'only',
            label: `${employee.first_name} had no other employer in ${year}`,
        },
    ];

    return (
        <>
            <Head title={`${employee.first_name}'s 2316`} />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={employeePage(employee.id).url}
                        aria-label={`Back to ${employee.name}`}
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            {employee.first_name}'s 2316
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Certificate you give your employee · {year}
                            {givenOn
                                ? ` · given ${formatDate(givenOn, 'long')}`
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
                    form="2316"
                    title="Certificate of Compensation Payment / Tax Withheld"
                    edition={edition}
                    parts={[
                        {
                            title: 'Part I · Employee',
                            split: true,
                            rows: [
                                { label: 'For the year', value: String(year) },
                                {
                                    label: 'Period',
                                    value: `${short(first)} 1 – ${short(last)} ${new Date(year, last, 0).getDate()}`,
                                },
                                {
                                    label: 'Employee TIN',
                                    value: employee.tin ?? '—',
                                },
                                {
                                    label: 'Employee name',
                                    value: `${employee.last_name}, ${employee.first_name} ${employee.middle_name ?? ''}`
                                        .trim()
                                        .toUpperCase(),
                                },
                            ],
                        },
                        {
                            title: 'Part II · Employer (you)',
                            split: true,
                            rows: [
                                {
                                    label: 'Employer name',
                                    value: (
                                        taxpayer?.full_name ?? ''
                                    ).toUpperCase(),
                                },
                            ],
                        },
                        {
                            title: 'Part III · Summary',
                            rows: [
                                {
                                    label: 'Taxable compensation',
                                    value: formAmount(pay.pay),
                                },
                                {
                                    label: 'Tax due',
                                    value: formAmount(pay.tax),
                                },
                                {
                                    label: `Tax withheld ${short(first)}–${short(before[before.length - 1]?.month ?? last)}`,
                                    value: formAmount(withheldBefore),
                                },
                                ...(pay.refund > 0
                                    ? [
                                          {
                                              label: `Refunded by ${formatDate(refundBy, 'long')}`,
                                              value: formAmount(-pay.refund),
                                          },
                                      ]
                                    : [
                                          {
                                              label: 'Withheld at year end',
                                              value: formAmount(
                                                  pay.withheld - withheldBefore,
                                              ),
                                          },
                                      ]),
                                {
                                    label: 'Total tax withheld',
                                    value: formAmount(
                                        pay.withheld - pay.refund,
                                    ),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    <Note title={`Give it by ${formatDate(dueDate, 'long')}`}>
                        It shows what you paid {employee.first_name} and the tax
                        withheld after the year-end check.
                    </Note>

                    {!givenOn && (
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
                                If {employee.first_name} worked elsewhere
                                earlier in {year}, ask for that 2316. The
                                year-end check must include it.
                            </p>
                        </SectionCard>
                    )}

                    <SectionCard>
                        <dl className="divide-y divide-divider text-sm">
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Form version
                                </dt>
                                <dd className="text-right font-bold">
                                    2316 · {edition}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Also file
                                </dt>
                                <dd className="text-right">
                                    <Link
                                        href={annual(year).url}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        Annual list of employees (1604-C),{' '}
                                        {formatDate(dueDate)}
                                    </Link>
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {givenOn ? (
                        <Note tone="success" title="Given">
                            Marked given on {formatDate(givenOn, 'long')}.
                        </Note>
                    ) : (
                        <Button
                            variant="outline"
                            disabled={saving}
                            onClick={() =>
                                router.post(given(args).url, undefined, {
                                    preserveScroll: true,
                                    onStart: () => setSaving(true),
                                    onFinish: () => setSaving(false),
                                })
                            }
                            className="h-12 rounded-xl border-2 border-primary text-[15px] font-bold text-primary"
                        >
                            Mark as given to {employee.first_name}
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
