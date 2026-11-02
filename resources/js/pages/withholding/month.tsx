import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { MarkFiledForm } from '@/components/mark-filed-form';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate, monthLong, peso } from '@/lib/format';
import { withholding } from '@/routes';
import { pdf } from '@/routes/withholding/month';
import { quarter as quarterPage } from '@/routes/withholding';
import type {
    WithholdingAgentIdentity,
    WithholdingMonth,
    WithholdingPayment,
} from '@/types';

type Props = {
    year: number;
    month: number;
    quarter: number;
    withheld: number;
    payments: WithholdingPayment[];
    status: WithholdingMonth;
    agent: WithholdingAgentIdentity;
    edition: string;
};

export default function WithholdingMonthPage({
    year,
    month,
    quarter,
    withheld,
    payments,
    status,
    agent,
    edition,
}: Props) {
    const name = monthLong(month);
    const args: [number, number] = [year, month];
    const filed = status.status === 'filed';
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const toCheck = [
        {
            key: 'recorded',
            label: `All ${name} payments with withholding are recorded`,
        },
        { key: 'tins', label: "Each payee's TIN is on file" },
    ];

    return (
        <>
            <Head title={`0619-E · ${name} ${year}`} />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={withholding(year).url}
                        aria-label="Back to withholding"
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            Your filled 0619-E
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Official BIR form · {name} {year} · {agent.name}
                            {filed && status.filed_on
                                ? ` · remitted ${formatDate(status.filed_on, 'long')}`
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
                    form="0619-E"
                    title="Monthly Remittance Form of Creditable Income Taxes Withheld (Expanded)"
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
                            title: 'Part II · Tax remittance',
                            rows: [
                                ...payments.map((payment) => ({
                                    label: `${payment.payee} · ${payment.atc}`,
                                    value: formAmount(payment.withheld),
                                })),
                                {
                                    label: 'Amount of remittance',
                                    value: formAmount(withheld),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    {status.due_date && (
                        <Note
                            title={`Due ${formatDate(status.due_date, 'short')}`}
                        >
                            {withheld > 0
                                ? `Remit what you withheld in ${name} by ${formatDate(status.due_date, 'long')}.`
                                : `Nothing withheld in ${name}, but the 0619-E is still filed, as a no-payment return, by ${formatDate(status.due_date, 'long')}. Filed late, it can draw a penalty even with nothing to pay.`}{' '}
                            {monthLong(quarter * 3)} has no 0619-E; it goes with
                            the quarterly 1601-EQ.
                        </Note>
                    )}

                    {!filed && (
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
                                Pay at an authorized agent bank or online with
                                the return.
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
                                    0619-E · {edition}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">Next</dt>
                                <dd className="text-right">
                                    <Link
                                        href={quarterPage([year, quarter]).url}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        Q{quarter} 1601-EQ with list of payees
                                    </Link>
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {filed ? (
                        <Note tone="success" title="Remitted">
                            {peso(status.amount_paid ?? withheld)} marked
                            remitted
                            {status.filed_on
                                ? ` on ${formatDate(status.filed_on, 'long')}`
                                : ''}
                            .
                        </Note>
                    ) : status.status === 'open' ? (
                        <p className="text-sm text-muted-foreground">
                            {name} isn't over yet. Mark it remitted once you've
                            paid, after the month ends.
                        </p>
                    ) : (
                        <SectionCard>
                            <h2 className="mb-3 text-lg font-extrabold">
                                Remitted it? Mark it paid
                            </h2>
                            <MarkFiledForm
                                year={year}
                                quarter={quarter}
                                month={month}
                                form="0619-E"
                                amountDue={withheld}
                            />
                        </SectionCard>
                    )}
                </div>
            </div>
        </>
    );
}
