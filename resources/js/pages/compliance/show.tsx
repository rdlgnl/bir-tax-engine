import { Head, Link } from '@inertiajs/react';
import { ChecklistPanel } from '@/components/compliance/checklist-panel';
import { CertificateTracker } from '@/components/compliance/certificate-tracker';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/compliance';
import type {
    CertificateTracker as Tracker,
    Checklist,
    QuarterResult,
} from '@/types';

type Props = {
    year: number;
    quarter: number;
    quarters: QuarterResult[];
    checklist: Checklist;
    certificates: Tracker;
    corOutdated: boolean;
};

const statusLabel: Record<QuarterResult['status'], string | null> = {
    filed: 'Filed',
    open: 'Open',
    due: null,
    overdue: 'Overdue',
};

export default function ComplianceShow({
    year,
    quarter,
    quarters,
    checklist,
    certificates,
    corOutdated,
}: Props) {
    const current = quarters.find((q) => q.quarter === quarter)!;
    const name = quarter === 4 ? 'Annual' : `Q${quarter}`;
    const byQuarter = Object.fromEntries(quarters.map((q) => [q.quarter, q]));

    return (
        <>
            <Head title="Compliance" />

            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Compliance
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {name} {year} · {checklist.done} of {checklist.total}{' '}
                        done ·{' '}
                        {current.status === 'filed'
                            ? `filed ${formatDate(current.filed_on!)}`
                            : `file by ${formatDate(current.due_date, 'short')}`}
                    </p>
                </div>
                <nav
                    aria-label="Quarter"
                    className="flex rounded-xl bg-[#E9ECF0] p-1"
                >
                    {quarters.map((q) => (
                        <Link
                            key={q.quarter}
                            href={show(q.quarter).url}
                            preserveScroll
                            aria-current={
                                q.quarter === quarter ? 'page' : undefined
                            }
                            className={cn(
                                'rounded-[10px] px-3 py-2 text-[13px] font-bold whitespace-nowrap text-slate-700',
                                q.quarter === quarter &&
                                    'bg-card text-foreground shadow-sm',
                            )}
                        >
                            {q.quarter === 4 ? 'Year' : `Q${q.quarter}`}
                            {statusLabel[q.status] &&
                                q.quarter !== quarter &&
                                ` · ${statusLabel[q.status]}`}
                        </Link>
                    ))}
                </nav>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-2">
                <ChecklistPanel
                    year={year}
                    quarter={current}
                    checklist={checklist}
                    corOutdated={corOutdated}
                />
                <CertificateTracker
                    year={year}
                    tracker={certificates}
                    quarters={byQuarter}
                />
            </div>
        </>
    );
}
