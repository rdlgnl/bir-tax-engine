import { Link } from '@inertiajs/react';
import { RequestEmail } from '@/components/compliance/request-email';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import { create, file } from '@/routes/certificates';
import type { CertificateTracker as Tracker, QuarterResult } from '@/types';

type Props = {
    year: number;
    tracker: Tracker;
    quarters: Record<number, QuarterResult | undefined>;
};

export function CertificateTracker({ year, tracker, quarters }: Props) {
    const missing = tracker.rows.find((row) => row.status === 'missing');
    const filed = tracker.filed;

    return (
        <SectionCard as="section">
            <div className="mb-4 flex items-center justify-between gap-3">
                <h2 className="text-xl font-extrabold">
                    Tax certificates (2307)
                </h2>
                <Button asChild className="rounded-xl font-bold">
                    <Link href={create().url}>Add 2307</Link>
                </Button>
            </div>

            <dl className="grid grid-cols-3 gap-2.5">
                <Stat label={`${year} credits`} value={tracker.credits} />
                <Stat
                    label="Confirmed"
                    value={tracker.confirmed}
                    tone="success"
                />
                <Stat label="Waiting" value={tracker.waiting} tone="warning" />
            </dl>

            {tracker.rows.length === 0 && !filed ? (
                <p className="mt-4 text-sm text-muted-foreground">
                    No client has withheld tax from you this year.
                </p>
            ) : (
                <ul className="mt-3">
                    {tracker.rows.map((row) => (
                        <li
                            key={`${row.quarter}-${row.counterparty}`}
                            className={cn(
                                'flex items-center gap-3 border-b border-divider px-2.5 py-3.5 text-[15px]',
                                row.status === 'missing' &&
                                    '-mx-2.5 rounded-xl border-0 bg-warning-soft px-5',
                            )}
                        >
                            <span className="min-w-0 flex-1 truncate font-bold">
                                {row.counterparty} · Q{row.quarter}
                            </span>
                            <RowStatus row={row} />
                            <span className="w-24 text-right font-extrabold">
                                {peso(row.amount)}
                            </span>
                        </li>
                    ))}
                    {filed && (
                        <li className="flex items-center gap-3 px-2.5 py-3.5 text-[15px]">
                            <span className="flex-1 font-bold">
                                {quarterRange(filed.quarters)} · {filed.count}{' '}
                                {filed.count === 1
                                    ? 'certificate'
                                    : 'certificates'}
                            </span>
                            <span
                                className={cn(
                                    'text-sm font-semibold',
                                    filed.all_received
                                        ? 'text-success'
                                        : 'text-warning',
                                )}
                            >
                                {filed.all_received
                                    ? 'All received'
                                    : 'Some missing'}
                            </span>
                            <span className="w-24 text-right font-extrabold">
                                {peso(filed.amount)}
                            </span>
                        </li>
                    )}
                </ul>
            )}

            {missing && (
                <RequestEmail
                    row={missing}
                    year={year}
                    quarter={quarters[missing.quarter] ?? null}
                />
            )}

            <p className="mt-4 text-sm text-muted-foreground">
                In January we'll remind you to send each client a sworn
                declaration, so they keep withholding 5% instead of 10%.
            </p>
        </SectionCard>
    );
}

function RowStatus({ row }: { row: Tracker['rows'][number] }) {
    if (row.status === 'received') {
        return (
            <span className="text-sm font-semibold text-success">
                {row.certificate_id ? (
                    <a
                        href={file(row.certificate_id).url}
                        target="_blank"
                        rel="noreferrer"
                        className="hover:underline"
                    >
                        Received{' '}
                        {row.received_on && formatDate(row.received_on)}
                    </a>
                ) : (
                    <>
                        Received{' '}
                        {row.received_on && formatDate(row.received_on)}
                    </>
                )}
            </span>
        );
    }

    return (
        <Link
            href={create({ query: { transaction: row.transaction_id } }).url}
            className={cn(
                'text-sm font-semibold hover:underline',
                row.status === 'missing'
                    ? 'text-warning'
                    : 'text-muted-foreground',
            )}
        >
            {row.status === 'missing'
                ? 'Not received · add'
                : `Due by ${formatDate(row.expected_by)}`}
        </Link>
    );
}

function Stat({
    label,
    value,
    tone,
}: {
    label: string;
    value: number;
    tone?: 'success' | 'warning';
}) {
    return (
        <div
            className={cn(
                'rounded-2xl px-3 py-3',
                tone === 'success'
                    ? 'bg-success-soft'
                    : tone === 'warning'
                      ? 'bg-warning-soft'
                      : 'bg-muted',
            )}
        >
            <dt
                className={cn(
                    'text-[13px]',
                    tone === 'success'
                        ? 'text-success'
                        : tone === 'warning'
                          ? 'text-warning'
                          : 'text-slate-700',
                )}
            >
                {label}
            </dt>
            <dd
                className={cn(
                    'text-xl font-extrabold sm:text-[22px]',
                    tone === 'success' && 'text-success',
                    tone === 'warning' && 'text-warning',
                )}
            >
                {peso(value)}
            </dd>
        </div>
    );
}

function quarterRange(quarters: number[]): string {
    return quarters.length === 1
        ? `Q${quarters[0]}`
        : `Q${quarters[0]}–Q${quarters[quarters.length - 1]}`;
}
