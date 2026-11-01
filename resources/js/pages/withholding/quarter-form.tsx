import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate, monthLong, peso } from '@/lib/format';
import { quarter as quarterPage } from '@/routes/withholding';
import { pdf } from '@/routes/withholding/quarter';
import type { WithholdingAgentIdentity, WithholdingQuarter } from '@/types';

type Props = WithholdingQuarter & {
    year: number;
    agent: WithholdingAgentIdentity;
    edition: string;
};

const ordinal = ['1st', '2nd', '3rd', '4th'];

export default function WithholdingQuarterForm({
    year,
    quarter,
    months,
    withheld,
    still_due,
    payees,
    due_date,
    status,
    agent,
    edition,
}: Props) {
    const args: [number, number] = [year, quarter];
    const back = quarterPage(args).url;
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const remitted = (n: 0 | 1) =>
        months[n].status === 'filed'
            ? (months[n].amount_paid ?? months[n].withheld)
            : 0;
    const toCheck = [
        {
            key: 'months',
            label: `${monthLong(months[0].month)} and ${monthLong(months[1].month)} 0619-E payments match`,
        },
        {
            key: 'payees',
            label: `The payee list totals ${peso(payees.reduce((sum, p) => sum + p.withheld, 0))} withheld`,
        },
    ];

    return (
        <>
            <Head title={`Filled 1601-EQ · Q${quarter} ${year}`} />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={back}
                        aria-label={`Back to Q${quarter} withholding`}
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            Your filled 1601-EQ
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Official BIR form · Q{quarter} {year} · {agent.name}{' '}
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
                    form="1601-EQ"
                    title="Quarterly Remittance Return of Creditable Income Taxes Withheld (Expanded)"
                    edition={edition}
                    parts={[
                        {
                            title: 'Part I · Background information',
                            split: true,
                            rows: [
                                {
                                    label: 'Quarter',
                                    value: `${ordinal[quarter - 1]} · ${year}`,
                                },
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
                            title: 'Part II · Computation of tax',
                            rows: [
                                {
                                    label: 'Total taxes withheld for the quarter',
                                    value: formAmount(withheld),
                                },
                                {
                                    label: 'Less: remitted, 1st month (0619-E)',
                                    value: formAmount(remitted(0)),
                                },
                                {
                                    label: 'Less: remitted, 2nd month (0619-E)',
                                    value: formAmount(remitted(1)),
                                },
                                {
                                    label: 'Tax still due',
                                    value: formAmount(still_due),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    <Note title={`Due ${formatDate(due_date, 'short')}`}>
                        On a weekend or holiday, file by the next working day.
                        Attach the list of payees (QAP).
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
                                Rates and ATCs per payee come from your rule
                                pack.
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
                                    1601-EQ · {edition}
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
                                        Quarterly list of payees (QAP)
                                    </Link>
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {status !== 'filed' && (
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
