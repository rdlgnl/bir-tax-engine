import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { FiguresForm } from '@/components/corporation/figures-form';
import { MarkCorporateFiled } from '@/components/corporation/mark-corporate-filed';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate, peso } from '@/lib/format';
import { edit, show } from '@/routes/corporation';
import { filed, pdf } from '@/routes/corporation/annual';
import type {
    CorporateAnnualEstimate,
    CorporateQuarterFigures,
    Corporation,
} from '@/types';

type Props = {
    corporation: Corporation;
    annual: CorporateAnnualEstimate;
    figures: CorporateQuarterFigures | null;
    edition: string;
    mcitRate: number;
};

const percent = (rate: number) => `${Math.round(rate * 100)}%`;

export default function CorporationAnnual({
    corporation,
    annual,
    figures,
    edition,
    mcitRate,
}: Props) {
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const f = annual.figures;
    const osd = corporation.deduction_method === 'osd';
    const yearOver = annual.status !== 'open';
    const missing = [
        !corporation.tin && 'TIN',
        !corporation.rdo_code && 'RDO code',
        !corporation.registered_address && 'registered address',
    ].filter(Boolean);
    const toCheck = [
        { key: 'audit', label: 'Audited financial statements are final' },
        { key: 'sawt', label: 'Every 2307 claimed is in the SAWT' },
        ...(osd
            ? []
            : [
                  {
                      key: 'schedule',
                      label: 'Deductions are broken down on Schedule I (page 3)',
                  },
              ]),
    ];

    return (
        <>
            <Head title="Filled 1702-RT" />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={show().url}
                        aria-label="Back to the corporation"
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            Your filled 1702-RT
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Official BIR form · {annual.year}
                            {annual.estimated &&
                                (yearOver
                                    ? ' · estimate until Q4 is in'
                                    : ' · estimate until Dec 31')}{' '}
                            · filled on this computer
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
                            href={pdf(annual.year, { query: { print: 1 } }).url}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Printer className="size-4" aria-hidden />
                            Print
                        </a>
                    </Button>
                    <Button asChild className="h-11 rounded-xl font-bold">
                        <a href={pdf(annual.year).url} download>
                            <Download className="size-4" aria-hidden />
                            Download PDF
                        </a>
                    </Button>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div className="flex flex-col gap-5">
                    <PaperForm
                        form="1702-RT"
                        title="Annual Income Tax Return (Corporations, Regular Rate)"
                        edition={edition}
                        parts={[
                            {
                                title: 'Part I · Background information',
                                split: true,
                                rows: [
                                    {
                                        label: 'For the year',
                                        value: `${annual.year} (calendar)`,
                                    },
                                    {
                                        label: 'TIN · RDO code',
                                        value: `${corporation.tin ?? '—'} · ${corporation.rdo_code ?? '—'}`,
                                    },
                                    {
                                        label: 'Registered name',
                                        value: corporation.name.toUpperCase(),
                                    },
                                ],
                            },
                            {
                                title: annual.estimated
                                    ? 'Part II · Computation (estimate)'
                                    : 'Part II · Computation',
                                rows: [
                                    {
                                        label: 'Sales/revenues',
                                        value: formAmount(f.sales),
                                    },
                                    {
                                        label: 'Less: cost of services',
                                        value: formAmount(f.cost_of_sales),
                                    },
                                    ...(f.other_income > 0
                                        ? [
                                              {
                                                  label: 'Add: other taxable income',
                                                  value: formAmount(
                                                      f.other_income,
                                                  ),
                                              },
                                          ]
                                        : []),
                                    {
                                        label: 'Gross income',
                                        value: formAmount(f.gross_income),
                                    },
                                    {
                                        label: osd
                                            ? 'Less: deductions (OSD, 40%)'
                                            : 'Less: deductions (itemized)',
                                        value: formAmount(f.deductions),
                                    },
                                    {
                                        label: 'Taxable income',
                                        value: formAmount(f.taxable_income),
                                    },
                                    {
                                        label: `Regular income tax (${percent(annual.rate)})`,
                                        value: formAmount(annual.regular_tax),
                                    },
                                    ...(annual.mcit_applies
                                        ? [
                                              {
                                                  label: `MCIT (${percent(mcitRate)})`,
                                                  value: formAmount(
                                                      annual.mcit,
                                                  ),
                                              },
                                          ]
                                        : []),
                                    {
                                        label: 'Tax due',
                                        value: formAmount(annual.tax_due),
                                    },
                                    {
                                        label: 'Less: Q1–Q3 payments',
                                        value: formAmount(
                                            annual.quarterly_payments,
                                        ),
                                    },
                                    {
                                        label: 'Less: creditable tax withheld',
                                        value: formAmount(annual.withheld),
                                    },
                                    {
                                        label: 'Tax payable',
                                        value: formAmount(annual.payable),
                                        total: true,
                                    },
                                ],
                            },
                        ]}
                    />

                    {!annual.filed_on && (
                        <FiguresForm
                            year={annual.year}
                            quarter={4}
                            figures={figures}
                            osd={osd}
                            startOpen={yearOver && annual.estimated}
                        />
                    )}
                </div>

                <div className="flex flex-col gap-5">
                    <Note
                        tone={annual.status === 'overdue' ? 'danger' : 'info'}
                        title={`File by ${formatDate(annual.due_date, 'short')}, ${annual.due_date.slice(0, 4)}`}
                    >
                        Attach audited financial statements and the SAWT.
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

                    {!annual.filed_on && (
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
                                {annual.estimated
                                    ? `Numbers lock after Dec 31 and the audit; the estimate assumes the rest of the year goes like the ${annual.quarters_entered === 1 ? 'quarter' : 'quarters'} so far.`
                                    : 'Use the audited figures; if they differ from the books, update the quarters first.'}
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
                                    1702-RT · {edition}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Attach
                                </dt>
                                <dd className="text-right font-bold text-primary">
                                    Audited financial statements, SAWT
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {annual.filed_on ? (
                        <Note
                            tone="success"
                            title={`${annual.year} 1702-RT filed`}
                        >
                            Marked filed on{' '}
                            {formatDate(annual.filed_on, 'long')}
                            {annual.amount_paid !== null &&
                                `, ${peso(annual.amount_paid)} paid`}
                            .
                        </Note>
                    ) : (
                        yearOver &&
                        !annual.estimated && (
                            <MarkCorporateFiled
                                action={filed(annual.year)}
                                payable={annual.payable}
                                form={`${annual.year} 1702-RT`}
                            />
                        )
                    )}
                </div>
            </div>
        </>
    );
}
