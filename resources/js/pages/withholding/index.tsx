import { Head, Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { formatDate, monthLong, peso, todayIso } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    annual,
    month as monthPage,
    quarter as quarterPage,
} from '@/routes/withholding';
import type { WithholdingMonth, WithholdingQuarter } from '@/types';

type YearPayee = {
    key: string;
    name: string;
    income: number;
    withheld: number;
    quarters: { quarter: number; given_on: string | null }[];
};

type Props = {
    year: number;
    quarters: WithholdingQuarter[];
    payees: YearPayee[];
    annualDue: string;
};

const pill: Record<WithholdingMonth['status'], string> = {
    filed: 'bg-success-soft text-success',
    due: 'bg-warning-soft text-warning',
    overdue: 'bg-red-50 text-destructive',
    open: 'bg-muted text-muted-foreground',
};

function Status({
    status,
    due,
    withheld,
    filedLabel,
}: {
    status: WithholdingMonth['status'];
    due: string;
    withheld: number;
    filedLabel: string;
}) {
    // With nothing withheld there is nothing to remit, so nothing is late.
    const nothing = status !== 'filed' && withheld <= 0;

    return (
        <span
            className={cn(
                'rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap',
                pill[nothing ? 'open' : status],
            )}
        >
            {nothing
                ? 'Nothing withheld'
                : status === 'filed'
                  ? filedLabel
                  : status === 'overdue'
                    ? `Overdue since ${formatDate(due)}`
                    : `Due ${formatDate(due)}`}
        </span>
    );
}

export default function WithholdingIndex({
    year,
    quarters,
    payees,
    annualDue,
}: Props) {
    const today = todayIso();
    const started = (month: number) =>
        `${year}-${String(month).padStart(2, '0')}-01` <= today;
    const shown = quarters.filter((q) => started(q.quarter * 3 - 2));
    const withheld = shown.reduce((sum, q) => sum + q.withheld, 0);
    const filedQuarters = shown.filter((q) => q.status === 'filed');
    // A filed quarter is remitted in full; otherwise only its marked months.
    const remitted = shown.reduce(
        (sum, q) => sum + (q.status === 'filed' ? q.withheld : q.remitted),
        0,
    );

    return (
        <>
            <Head title={`Withholding · ${year}`} />

            <header className="mb-6">
                <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                    Withholding on your payments
                </h1>
                <p className="text-[15px] text-muted-foreground">
                    {year} · tax you keep from fees and rent you pay, and send
                    to the BIR
                </p>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <SectionCard title={`${year} so far`}>
                        <dl className="text-[15px] tabular-nums">
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Payees</dt>
                                <dd className="font-bold">{payees.length}</dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Income payments</dt>
                                <dd className="font-bold">
                                    {peso(
                                        payees.reduce(
                                            (sum, p) => sum + p.income,
                                            0,
                                        ),
                                    )}
                                </dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Tax withheld</dt>
                                <dd className="font-bold">{peso(withheld)}</dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Remitted</dt>
                                <dd className="font-bold text-success">
                                    {peso(remitted)}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between pt-3">
                                <dt className="font-bold">Still to remit</dt>
                                <dd className="text-xl font-extrabold">
                                    {peso(Math.max(0, withheld - remitted))}
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {/* The latest quarter first. */}
                    {[...shown].reverse().map((q) => (
                        <SectionCard key={q.quarter} title={`Q${q.quarter}`}>
                            <ul className="divide-y divide-divider">
                                {q.months.map((m, i) =>
                                    i < 2 && started(m.month) ? (
                                        <li key={m.month}>
                                            <Link
                                                href={
                                                    monthPage([year, m.month])
                                                        .url
                                                }
                                                className="flex items-center justify-between gap-3 py-3 hover:bg-muted/50"
                                            >
                                                <span>
                                                    <span className="block text-[15px] font-bold">
                                                        {monthLong(m.month)}
                                                    </span>
                                                    <span className="text-sm text-muted-foreground">
                                                        0619-E ·{' '}
                                                        {peso(m.withheld)}{' '}
                                                        withheld
                                                    </span>
                                                </span>
                                                <span className="flex items-center gap-2">
                                                    {m.status !== 'open' &&
                                                        m.due_date && (
                                                            <Status
                                                                status={
                                                                    m.status
                                                                }
                                                                due={m.due_date}
                                                                withheld={
                                                                    m.withheld
                                                                }
                                                                filedLabel="Remitted"
                                                            />
                                                        )}
                                                    <ChevronRight
                                                        className="size-4 text-muted-foreground"
                                                        aria-hidden
                                                    />
                                                </span>
                                            </Link>
                                        </li>
                                    ) : (
                                        <li
                                            key={m.month}
                                            className="flex items-center justify-between gap-3 py-3"
                                        >
                                            <span>
                                                <span className="block text-[15px] font-bold">
                                                    {monthLong(m.month)}
                                                </span>
                                                <span className="text-sm text-muted-foreground">
                                                    {i === 2
                                                        ? 'Goes on the 1601-EQ'
                                                        : 'Not started'}{' '}
                                                    · {peso(m.withheld)}{' '}
                                                    withheld
                                                </span>
                                            </span>
                                        </li>
                                    ),
                                )}
                                <li>
                                    <Link
                                        href={
                                            quarterPage([year, q.quarter]).url
                                        }
                                        className="flex items-center justify-between gap-3 py-3 hover:bg-muted/50"
                                    >
                                        <span>
                                            <span className="block text-[15px] font-bold">
                                                Q{q.quarter} return · 1601-EQ
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                {q.payees.length} payee
                                                {q.payees.length === 1
                                                    ? ''
                                                    : 's'}{' '}
                                                · {peso(q.still_due)} still to
                                                remit
                                            </span>
                                        </span>
                                        <span className="flex items-center gap-2">
                                            {q.status !== 'open' && (
                                                <Status
                                                    status={q.status}
                                                    due={q.due_date}
                                                    withheld={q.withheld}
                                                    filedLabel="Filed"
                                                />
                                            )}
                                            <ChevronRight
                                                className="size-4 text-muted-foreground"
                                                aria-hidden
                                            />
                                        </span>
                                    </Link>
                                </li>
                            </ul>
                        </SectionCard>
                    ))}

                    <SectionCard title="Payees">
                        {payees.length === 0 ? (
                            <p className="py-2 text-sm text-muted-foreground">
                                No payments with withholding yet. When you
                                record an expense, say whether you withheld tax
                                from it.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm tabular-nums">
                                    <thead>
                                        <tr className="border-b text-left text-xs font-bold text-slate-600">
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2"
                                            >
                                                Payee
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2 text-right"
                                            >
                                                Income paid
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2 text-right"
                                            >
                                                Withheld
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2 text-right"
                                            >
                                                2307s given
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {payees.map((payee) => {
                                            const given = payee.quarters.filter(
                                                (q) => q.given_on !== null,
                                            ).length;

                                            return (
                                                <tr
                                                    key={payee.key}
                                                    className="border-b border-divider"
                                                >
                                                    <td className="px-1.5 py-3">
                                                        {payee.name}
                                                    </td>
                                                    <td className="px-1.5 py-3 text-right">
                                                        {peso(payee.income)}
                                                    </td>
                                                    <td className="px-1.5 py-3 text-right">
                                                        {peso(payee.withheld)}
                                                    </td>
                                                    <td className="px-1.5 py-3 text-right font-bold">
                                                        {payee.quarters
                                                            .length === 1
                                                            ? `Q${payee.quarters[0].quarter} ${given ? 'given' : 'pending'}`
                                                            : `${given} of ${payee.quarters.length}`}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <Note title="How it works">
                        Keep the tax from each fee or rent you pay. Remit the
                        first two months of a quarter with 0619-E by the 10th of
                        the next month, and the quarter with 1601-EQ and its
                        list of payees. Give each payee a 2307.
                    </Note>
                    <Note
                        tone="warning"
                        title={`Annual list due ${formatDate(annualDue, 'short')}`}
                    >
                        All {year} payees go on the 1604-E, due{' '}
                        {formatDate(annualDue, 'long')}
                        {filedQuarters.length < 4 &&
                            ', once every quarter is filed'}
                        .{' '}
                        <Link
                            href={annual(year).url}
                            className="font-bold underline"
                        >
                            See the list
                        </Link>
                    </Note>
                </div>
            </div>
        </>
    );
}
