import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate } from '@/lib/format';
import { annual as annualPage } from '@/routes/withholding';
import { pdf } from '@/routes/withholding/annual';
import type { WithholdingAgentIdentity, WithholdingAnnual } from '@/types';

type Props = WithholdingAnnual & {
    agent: WithholdingAgentIdentity;
    edition: string;
};

export default function WithholdingAnnualForm({
    year,
    quarters,
    withheld,
    due_date,
    status,
    agent,
    edition,
}: Props) {
    const back = annualPage(year).url;
    const open = status === 'open';
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const toCheck = [
        { key: 'payees', label: `Every ${year} payee is listed` },
        {
            key: 'totals',
            label: 'Totals match the 0619-E and 1601-EQ filed',
        },
    ];

    return (
        <>
            <Head title={`Filled 1604-E · ${year}`} />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={back}
                        aria-label="Back to the annual list of payees"
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            Your filled 1604-E
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Official BIR form · {year}
                            {open ? ' · draft until Dec 31' : ''} · filled on
                            this computer
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
                    form="1604-E"
                    title="Annual Information Return of Creditable Income Taxes Withheld (Expanded)"
                    edition={edition}
                    parts={[
                        {
                            title: 'Part I · Background information',
                            split: true,
                            rows: [
                                { label: 'For the year', value: String(year) },
                                {
                                    label: 'TIN · RDO code',
                                    value: `${agent.tin ?? '—'} · ${agent.rdo ?? '—'}`,
                                },
                                {
                                    label: 'Withholding agent',
                                    value: agent.name.toUpperCase(),
                                },
                                { label: 'Category', value: 'Private' },
                            ],
                        },
                        {
                            title: 'Part II · Summary of remittances (1601-EQ)',
                            rows: [
                                ...quarters
                                    .filter((q) => q.withheld > 0)
                                    .map((q) => ({
                                        label:
                                            q.status === 'filed'
                                                ? `Q${q.quarter}`
                                                : `Q${q.quarter} (1601-EQ due ${formatDate(q.due_date)})`,
                                        value: formAmount(q.withheld),
                                    })),
                                {
                                    label: open
                                        ? 'Total withheld so far'
                                        : 'Total withheld',
                                    value: formAmount(withheld),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    <Note title={`File by ${formatDate(due_date, 'full')}`}>
                        No payment; it lists every payee and must match your
                        monthly and quarterly remittances.
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
                            {open && (
                                <p className="mt-2 text-[13px] text-warning">
                                    The rest of the year is added as you record
                                    it.
                                </p>
                            )}
                        </SectionCard>
                    )}

                    <SectionCard>
                        <dl className="divide-y divide-divider text-sm">
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Form version
                                </dt>
                                <dd className="text-right font-bold">
                                    1604-E · {edition}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Attach
                                </dt>
                                <dd className="text-right">
                                    <Link
                                        href={back}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        Annual alphalist of payees (data file)
                                    </Link>
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {status !== 'filed' && !open && (
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl border-2 border-primary text-[15px] font-bold text-primary"
                        >
                            <Link href={back}>
                                I've filed it · mark as filed
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
