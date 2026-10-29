import { AlertTriangle, Check } from 'lucide-react';
import { CardLink, SectionCard } from '@/components/section-card';
import { Progress } from '@/components/ui/progress';
import { formatDate, peso } from '@/lib/format';
import { show as complianceShow } from '@/routes/compliance';
import type { Checklist, QuarterResult } from '@/types';

export function ChecklistCard({
    checklist,
    quarter,
}: {
    checklist: Checklist;
    quarter: QuarterResult;
}) {
    const done = checklist.items.filter((item) => item.done);
    const pending = checklist.items.filter(
        (item) => !item.done && item.key !== 'certificates',
    );

    return (
        <SectionCard
            title={`Compliance · ${checklist.done} of ${checklist.total}`}
            action={
                <CardLink href={complianceShow(quarter.quarter).url}>
                    Checklist
                </CardLink>
            }
        >
            <Progress
                value={(checklist.done / checklist.total) * 100}
                aria-label={`${checklist.done} of ${checklist.total} done`}
                className="h-2 bg-divider [&>[data-slot=progress-indicator]]:bg-success"
            />
            <ul className="mt-3 flex flex-wrap gap-x-2 gap-y-1 text-sm">
                {done.map((item) => (
                    <li key={item.key} className="flex items-center gap-1">
                        <Check className="size-4 text-success" aria-hidden />
                        {item.label}
                    </li>
                ))}
            </ul>
            {checklist.missing_certificates.map((missing) => (
                <p
                    key={missing.counterparty}
                    className="mt-3 flex items-start gap-2 rounded-xl bg-warning-soft px-3 py-2.5 text-sm font-semibold text-warning"
                >
                    <AlertTriangle
                        className="mt-0.5 size-4 shrink-0"
                        aria-hidden
                    />
                    2307 missing from {missing.counterparty} (
                    {peso(missing.amount)} credit)
                </p>
            ))}
            {pending.length > 0 && quarter.status !== 'open' && (
                <p className="mt-3 text-sm text-muted-foreground">
                    {pending.map((item) => item.label).join(' · ')} pending ·
                    due {formatDate(quarter.due_date)}
                </p>
            )}
        </SectionCard>
    );
}
