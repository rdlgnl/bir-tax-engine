import { Deferred, Head, Link } from '@inertiajs/react';
import { AlertTriangle, FileText } from 'lucide-react';
import { useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { ReportsCard } from '@/components/returns/reports-card';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, formAmount, peso, signedPeso } from '@/lib/format';
import { cn } from '@/lib/utils';
import { history } from '@/routes';
import { amend, pdf, show as prepare } from '@/routes/returns';
import type { HistoryDetail, HistoryEntry, ReportYear } from '@/types';

type Props = {
    entries: HistoryEntry[];
    detail: HistoryDetail | null;
    reports?: ReportYear[];
};

type Filter = 'all' | 'filed' | 'open';

const channels: Record<string, string> = {
    online: 'online bank or e-wallet',
    bank: 'a bank counter',
    bir_office: 'a BIR office',
};

const events: Record<string, string> = {
    created: 'Added',
    updated: 'Edited',
    deleted: 'Deleted',
    restored: 'Restored',
};

function key(e: { year: number; quarter: number; form: string }): string {
    return `${e.year}-${e.quarter}-${e.form}`;
}

export default function HistoryIndex({ entries, detail, reports }: Props) {
    const [filter, setFilter] = useState<Filter>('all');
    const visible = entries.filter((e) =>
        filter === 'all'
            ? true
            : filter === 'filed'
              ? e.status === 'filed'
              : e.status !== 'filed',
    );
    const years = [...new Set(visible.map((e) => e.year))];
    const selected = detail ? key(detail.entry) : null;

    return (
        <>
            <Head title="History" />

            <header className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                    History
                </h1>
                <ChoiceChips<Filter>
                    label="Show"
                    value={filter}
                    onChange={setFilter}
                    options={[
                        { value: 'all', label: 'All' },
                        { value: 'filed', label: 'Filed' },
                        { value: 'open', label: 'Not filed yet' },
                    ]}
                />
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[360px_minmax(0,1fr)]">
                <SectionCard as="section" className="p-4">
                    <h2 className="sr-only">Returns</h2>
                    {years.length === 0 && (
                        <p className="p-2 text-sm text-muted-foreground">
                            Nothing here yet.
                        </p>
                    )}
                    {years.map((year) => (
                        <div key={year} className="mb-2">
                            <h3 className="px-2 py-2 text-[13px] font-extrabold text-slate-700">
                                {year}
                            </h3>
                            <ul className="flex flex-col gap-1">
                                {visible
                                    .filter((e) => e.year === year)
                                    .map((e) => (
                                        <li key={key(e)}>
                                            <Link
                                                href={
                                                    history({
                                                        query: {
                                                            year: e.year,
                                                            quarter: e.quarter,
                                                            form: e.form,
                                                        },
                                                    }).url
                                                }
                                                only={['detail']}
                                                preserveState
                                                preserveScroll
                                                aria-current={
                                                    selected === key(e)
                                                        ? 'true'
                                                        : undefined
                                                }
                                                className={cn(
                                                    'flex items-start justify-between gap-3 rounded-xl px-3 py-2.5 hover:bg-muted',
                                                    selected === key(e) &&
                                                        'bg-accent hover:bg-accent',
                                                )}
                                            >
                                                <span>
                                                    <span className="block text-[15px] font-bold">
                                                        {e.title}
                                                        <span className="font-normal text-muted-foreground">
                                                            {' '}
                                                            · {e.form}
                                                        </span>
                                                    </span>
                                                    <EntryStatus entry={e} />
                                                </span>
                                                <span className="font-extrabold">
                                                    {peso(e.amount)}
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                            </ul>
                        </div>
                    ))}
                </SectionCard>

                {detail ? <Detail detail={detail} /> : null}
            </div>

            <div className="mt-5">
                <Deferred
                    data="reports"
                    fallback={
                        <SectionCard className="h-40 animate-pulse">
                            {null}
                        </SectionCard>
                    }
                >
                    {reports ? <ReportsCard reports={reports} /> : null}
                </Deferred>
            </div>
        </>
    );
}

function EntryStatus({ entry }: { entry: HistoryEntry }) {
    switch (entry.status) {
        case 'filed':
            return (
                <span className="text-[13px] text-success">
                    Filed {formatDate(entry.filed_on!)}
                    {entry.paid_on ? ' and paid' : ' · not paid yet'}
                </span>
            );
        case 'overdue':
            return (
                <span className="text-[13px] text-destructive">
                    Overdue since {formatDate(entry.due_date)}
                </span>
            );
        case 'due':
            return (
                <span className="text-[13px] text-warning">
                    Due {formatDate(entry.due_date)}
                </span>
            );
        default:
            return (
                <span className="text-[13px] text-muted-foreground">
                    Estimate · due {formatDate(entry.due_date)}
                </span>
            );
    }
}

function Detail({ detail }: { detail: HistoryDetail }) {
    const { entry, layout, filing, drift, amendment, amended, changes } =
        detail;
    const args: [number, number, string] = [
        entry.year,
        entry.quarter,
        entry.form,
    ];
    const heading =
        entry.quarter === 4 && entry.form !== '2551Q'
            ? `${entry.year} annual return`
            : `Q${entry.quarter} ${entry.year}`;

    return (
        <SectionCard as="section" className="flex flex-col gap-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-xl font-extrabold">{heading}</h2>
                    <p className="text-sm text-muted-foreground">
                        BIR Form {layout.form} · {layout.method}
                        {filing?.frozen && ' · frozen as filed'}
                    </p>
                </div>
                <div className="flex flex-wrap gap-1.5">
                    {filing ? (
                        <>
                            <Badge tone="success">
                                Filed {formatDate(filing.filed_on)}
                            </Badge>
                            <Badge
                                tone={filing.paid_on ? 'success' : 'warning'}
                            >
                                {filing.paid_on
                                    ? `Paid ${formatDate(filing.paid_on)}`
                                    : 'Not paid yet'}
                            </Badge>
                        </>
                    ) : (
                        <Badge tone="info">Estimate until filed</Badge>
                    )}
                </div>
            </div>

            <dl className="text-[15px]">
                {layout.lines.map((line) => (
                    <div
                        key={line.label}
                        className={cn(
                            'flex justify-between gap-4 border-b border-divider py-2.5',
                            line.kind === 'total' && 'font-bold',
                        )}
                    >
                        <dt>{line.label}</dt>
                        <dd className="whitespace-nowrap">
                            {formAmount(line.amount)}
                        </dd>
                    </div>
                ))}
            </dl>

            {drift && (
                <p className="flex items-start gap-2.5 rounded-xl bg-warning-soft px-4 py-3 text-sm text-warning">
                    <AlertTriangle
                        className="mt-0.5 size-4 shrink-0"
                        aria-hidden
                    />
                    Your records changed after you filed. Computed today this
                    return would be {peso(drift.payable_now)} (
                    {signedPeso(drift.difference)}). The filed figures above
                    stay as they were.{' '}
                    {amendment && (
                        <Link
                            href={amend(args).url}
                            className="font-bold underline"
                        >
                            Amend Q{entry.quarter}
                            {amendment.extra > 0
                                ? ` · ${peso(amendment.extra)} more to pay`
                                : ''}
                        </Link>
                    )}
                </p>
            )}

            {amended.map((a) => (
                <p key={a.number} className="text-sm text-slate-700">
                    Amended return{amended.length > 1 ? ` ${a.number}` : ''}{' '}
                    filed {formatDate(a.filed_on, 'long')}
                    {a.amount_paid !== null
                        ? ` · paid ${peso(a.amount_paid)}`
                        : ''}
                    .
                </p>
            ))}

            {filing && (
                <p className="text-sm text-slate-700">
                    {filing.reference_number && (
                        <>Reference {filing.reference_number}. </>
                    )}
                    {filing.paid_on
                        ? `Paid ${peso(filing.amount_paid ?? 0)} on ${formatDate(filing.paid_on, 'long')}${filing.payment_channel ? ` through ${channels[filing.payment_channel] ?? filing.payment_channel}` : ''}.`
                        : 'Payment not recorded yet.'}
                </p>
            )}

            <div className="flex flex-wrap gap-2.5">
                <Button
                    asChild
                    variant="outline"
                    className="rounded-xl font-bold"
                >
                    <a href={pdf(args).url} download>
                        <FileText className="size-4" aria-hidden />
                        {filing
                            ? `Filed ${layout.form} (PDF)`
                            : `Draft ${layout.form} (PDF)`}
                    </a>
                </Button>
                {!filing && entry.status !== 'open' && (
                    <Button asChild className="rounded-xl font-bold">
                        <Link href={prepare(args).url}>
                            Prepare {layout.form}
                        </Link>
                    </Button>
                )}
            </div>

            {filing && (
                <details className="rounded-xl border px-4 py-3">
                    <summary className="cursor-pointer text-[15px] font-bold">
                        Changes since filed · {changes.length}
                    </summary>
                    {changes.length === 0 ? (
                        <p className="mt-2 text-sm text-muted-foreground">
                            Nothing this return was computed from has changed
                            since you marked it filed.
                        </p>
                    ) : (
                        <ul className="mt-2 flex flex-col divide-y divide-divider text-sm">
                            {changes.map((c, i) => (
                                <li key={i} className="py-2">
                                    <span className="block font-bold">
                                        {events[c.event]} · {c.label}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {formatDate(c.at.slice(0, 10), 'long')}{' '}
                                        · {c.cause}
                                    </span>
                                    {c.event === 'updated' && (
                                        <ul className="mt-1 text-slate-700">
                                            {c.fields.map((f) => (
                                                <li key={f.field}>
                                                    {f.field.replaceAll(
                                                        '_',
                                                        ' ',
                                                    )}
                                                    : {f.before ?? '—'} →{' '}
                                                    {f.after ?? '—'}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </details>
            )}

            <details className="rounded-xl bg-muted px-4 py-3">
                <summary className="cursor-pointer text-[15px] font-bold">
                    Audit trail · rules used
                </summary>
                <ul className="mt-2 flex flex-col gap-1 text-sm text-slate-700">
                    <li>Rules: {layout.rules.join(' · ')}</li>
                    <li>Rule pack {layout.rule_pack}</li>
                    {filing?.frozen && (
                        <li>
                            Figures frozen{' '}
                            {formatDate(layout.taken_at.slice(0, 10), 'long')}
                        </li>
                    )}
                    {filing && (
                        <li>
                            {formatDate(filing.filed_on, 'long')} · marked filed
                        </li>
                    )}
                    {filing?.paid_on && (
                        <li>
                            {formatDate(filing.paid_on, 'long')} · paid{' '}
                            {peso(filing.amount_paid ?? 0)}
                        </li>
                    )}
                </ul>
            </details>
        </SectionCard>
    );
}

function Badge({
    tone,
    children,
}: {
    tone: 'success' | 'warning' | 'info';
    children: React.ReactNode;
}) {
    return (
        <span
            className={cn(
                'rounded-full px-2.5 py-1 text-xs font-bold',
                tone === 'success' && 'bg-success-soft text-success',
                tone === 'warning' && 'bg-warning-soft text-warning',
                tone === 'info' && 'bg-accent text-accent-foreground',
            )}
        >
            {children}
        </span>
    );
}
