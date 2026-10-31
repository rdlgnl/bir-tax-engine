import { Link } from '@inertiajs/react';
import { AlertTriangle, Check, Circle } from 'lucide-react';
import { MarkPaidDialog } from '@/components/mark-paid-dialog';
import { SectionCard } from '@/components/section-card';
import { Progress } from '@/components/ui/progress';
import { show as prepare } from '@/routes/returns';
import { registration } from '@/routes';
import { show as taxShow } from '@/routes/tax';
import { index as transactionsIndex } from '@/routes/transactions';
import type { Checklist, ChecklistItem, QuarterResult } from '@/types';

type Props = {
    year: number;
    quarter: QuarterResult;
    checklist: Checklist;
    corOutdated: boolean;
};

const actionClass = 'shrink-0 text-sm font-bold text-primary hover:underline';

export function ChecklistPanel({
    year,
    quarter,
    checklist,
    corOutdated,
}: Props) {
    function action(item: ChecklistItem) {
        switch (item.action) {
            case 'transactions':
                return (
                    <Link
                        href={
                            transactionsIndex({
                                query: { year, quarter: quarter.quarter },
                            }).url
                        }
                        className={actionClass}
                    >
                        View
                    </Link>
                );
            case 'tax':
                return (
                    <Link
                        href={taxShow(quarter.quarter).url}
                        className={actionClass}
                    >
                        View
                    </Link>
                );
            case 'file':
                return (
                    <Link
                        href={
                            prepare([year, quarter.quarter, quarter.form]).url
                        }
                        className={actionClass}
                    >
                        Prepare
                    </Link>
                );
            case 'pay':
                return checklist.filing ? (
                    <MarkPaidDialog
                        filing={checklist.filing}
                        amountDue={quarter.due}
                        trigger={
                            <button type="button" className={actionClass}>
                                Mark paid
                            </button>
                        }
                    />
                ) : null;
            default:
                return null;
        }
    }

    return (
        <SectionCard as="section" className="flex flex-col">
            <h2 className="sr-only">Checklist</h2>
            <Progress
                value={(checklist.done / checklist.total) * 100}
                aria-label={`${checklist.done} of ${checklist.total} done`}
                className="mb-2 h-2 bg-divider [&>[data-slot=progress-indicator]]:bg-success"
            />
            <ul className="divide-y divide-divider">
                {checklist.items.map((item) => (
                    <li
                        key={item.key}
                        className="flex items-center gap-3.5 py-3.5"
                    >
                        <StateIcon item={item} />
                        <div className="min-w-0 flex-1">
                            <p className="text-[15px] font-bold">
                                {item.label}
                            </p>
                            <p
                                className={
                                    item.state === 'warning'
                                        ? 'text-[13px] text-warning'
                                        : 'text-[13px] text-muted-foreground'
                                }
                            >
                                {item.detail}
                            </p>
                        </div>
                        {action(item)}
                    </li>
                ))}
            </ul>
            {corOutdated && (
                <p className="mt-3 rounded-xl bg-muted px-3.5 py-3 text-sm">
                    Not blocking: your COR still lists percentage tax.{' '}
                    <Link
                        href={registration().url}
                        className="font-bold text-primary underline"
                    >
                        Update with Form 1905
                    </Link>
                </p>
            )}
        </SectionCard>
    );
}

function StateIcon({ item }: { item: ChecklistItem }) {
    if (item.state === 'done') {
        return (
            <Check className="size-5 shrink-0 text-success" aria-label="Done" />
        );
    }

    if (item.state === 'warning') {
        return (
            <AlertTriangle
                className="size-5 shrink-0 text-warning"
                aria-label="Needs attention"
            />
        );
    }

    return (
        <Circle
            className="size-5 shrink-0 text-slate-400"
            aria-label="Not done yet"
        />
    );
}
