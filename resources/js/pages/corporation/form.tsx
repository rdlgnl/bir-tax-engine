import { BetaBadge, BetaNote } from '@/components/corporation/beta';
import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { MarkCorporateFiled } from '@/components/corporation/mark-corporate-filed';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate } from '@/lib/format';
import { edit, filed, pdf, quarter as quarterPage } from '@/routes/corporation';
import type { CorporateQuarterTax, Corporation } from '@/types';

type Props = {
    corporation: Corporation;
    tax: CorporateQuarterTax;
    edition: string;
    mcitRate: number;
    annualDue: string;
};

const percent = (rate: number) => `${Math.round(rate * 100)}%`;
const ordinal = ['1st', '2nd', '3rd'];

export default function CorporationForm({
    corporation,
    tax,
    edition,
    mcitRate,
    annualDue,
}: Props) {
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const t = tax.to_date;
    const route = [tax.year, tax.quarter] as [number, number];
    const missing = [
        !corporation.tin && 'TIN',
        !corporation.rdo_code && 'RDO code',
        !corporation.registered_address && 'registered address',
    ].filter(Boolean);
    const toCheck = [
        {
            key: 'rate',
            label: tax.small
                ? '20% rate, provisional: taxable income so far ≤ ₱5M and assets ≤ ₱100M (excluding the land under the office); the annual return tests the year'
                : '25% rate: taxable income or assets above the 20% limits',
        },
        {
            key: 'sawt',
            label: '2307s from clients are attached via SAWT',
        },
    ];

    return (
        <>
            <Head title="Filled 1702Q" />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={quarterPage(route).url}
                        aria-label="Back to the quarter"
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            Your filled 1702Q
                            <BetaBadge />
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Official BIR form · Q{tax.quarter} {tax.year} ·{' '}
                            {corporation.name} · filled on this computer
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
                            href={pdf(route, { query: { print: 1 } }).url}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Printer className="size-4" aria-hidden />
                            Print
                        </a>
                    </Button>
                    <Button asChild className="h-11 rounded-xl font-bold">
                        <a href={pdf(route).url} download>
                            <Download className="size-4" aria-hidden />
                            Download PDF
                        </a>
                    </Button>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
                <PaperForm
                    form="1702Q"
                    title="Quarterly Income Tax Return (Corporations, Partnerships)"
                    edition={edition}
                    parts={[
                        {
                            title: 'Part I · Background information',
                            split: true,
                            rows: [
                                {
                                    label: 'Quarter',
                                    value: `${ordinal[tax.quarter - 1]} · ${tax.year}`,
                                },
                                {
                                    label: 'TIN · RDO code',
                                    value: `${corporation.tin ?? '—'} · ${corporation.rdo_code ?? '—'}`,
                                },
                                {
                                    label: 'Registered name',
                                    value: corporation.name.toUpperCase(),
                                },
                                {
                                    label: 'ATC',
                                    value:
                                        tax.uses === 'mcit'
                                            ? 'IC055 (MCIT)'
                                            : 'IC010',
                                },
                            ],
                        },
                        {
                            title: `Part II · Computation (${['Jan–Mar', 'Jan–Jun', 'Jan–Sep'][tax.quarter - 1]}, cumulative)`,
                            rows: [
                                {
                                    label: 'Sales/revenues',
                                    value: formAmount(t.sales),
                                },
                                {
                                    label: 'Less: cost of services',
                                    value: formAmount(t.cost_of_sales),
                                },
                                ...(t.other_income > 0
                                    ? [
                                          {
                                              label: 'Add: other taxable income',
                                              value: formAmount(t.other_income),
                                          },
                                      ]
                                    : []),
                                {
                                    label: 'Gross income',
                                    value: formAmount(t.gross_income),
                                },
                                {
                                    label:
                                        corporation.deduction_method === 'osd'
                                            ? 'Less: deductions (OSD, 40%)'
                                            : 'Less: deductions (itemized)',
                                    value: formAmount(t.deductions),
                                },
                                {
                                    label: 'Taxable income',
                                    value: formAmount(t.taxable_income),
                                },
                                {
                                    label: `Regular income tax (${percent(tax.rate)})`,
                                    value: formAmount(tax.regular_tax),
                                },
                                ...(tax.mcit_applies
                                    ? [
                                          {
                                              label: `MCIT (${percent(mcitRate)} of gross income)`,
                                              value: formAmount(tax.mcit),
                                          },
                                      ]
                                    : []),
                                {
                                    label: tax.mcit_applies
                                        ? 'Tax due (higher of the two)'
                                        : 'Tax due',
                                    value: formAmount(tax.tax_due),
                                },
                                ...(tax.quarter > 1
                                    ? [
                                          {
                                              label: `Less: ${tax.quarter === 2 ? 'Q1' : 'Q1–Q2'} payments`,
                                              value: formAmount(
                                                  tax.paid_regular +
                                                      tax.paid_mcit,
                                              ),
                                          },
                                      ]
                                    : []),
                                {
                                    label: 'Less: creditable tax withheld',
                                    value: formAmount(
                                        tax.withheld_previous +
                                            tax.withheld_this_quarter,
                                    ),
                                },
                                {
                                    label: 'Tax payable',
                                    value: formAmount(tax.payable),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    <BetaNote />
                    <Note
                        tone={tax.status === 'overdue' ? 'danger' : 'info'}
                        title={`Due ${formatDate(tax.due_date, 'full')}`}
                    >
                        {tax.due_note}
                    </Note>

                    {missing.length > 0 && (
                        <Note tone="warning" title="Blank on the form">
                            The corporation's {missing.join(', ')}.{' '}
                            <Link
                                href={edit().url}
                                className="font-bold underline"
                            >
                                Add them
                            </Link>
                        </Note>
                    )}

                    {!tax.filed_on && (
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
                                The 20% rate depends on total assets; confirm
                                the balance sheet figure each year.
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
                                    1702Q · {edition}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Year end
                                </dt>
                                <dd className="text-right font-bold text-primary">
                                    Annual 1702-RT by {formatDate(annualDue)}
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {tax.filed_on ? (
                        <Note tone="success" title={`Q${tax.quarter} filed`}>
                            Marked filed on {formatDate(tax.filed_on, 'long')}.
                        </Note>
                    ) : (
                        tax.status !== 'open' && (
                            <MarkCorporateFiled
                                action={filed(route)}
                                payable={tax.payable}
                                form={`Q${tax.quarter} 1702Q`}
                            />
                        )
                    )}
                </div>
            </div>
        </>
    );
}
