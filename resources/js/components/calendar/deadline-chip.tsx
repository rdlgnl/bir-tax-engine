import { peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Deadline } from '@/types';

export function chipTone(deadline: Deadline): string {
    if (deadline.status === 'filed') {
        return 'bg-success-soft text-success';
    }

    if (deadline.kind === 'decision') {
        return 'bg-warning-soft text-warning';
    }

    if (deadline.kind === 'reminder') {
        return 'bg-muted text-slate-700';
    }

    if (deadline.status === 'overdue') {
        return 'bg-destructive text-white';
    }

    return deadline.status === 'due'
        ? 'bg-primary text-primary-foreground'
        : 'bg-accent text-accent-foreground';
}

export function DeadlineChip({ deadline }: { deadline: Deadline }) {
    return (
        <span
            className={cn(
                'block rounded-lg px-2 py-1.5 text-[11px] leading-tight font-extrabold',
                chipTone(deadline),
            )}
        >
            <span className="block uppercase">
                {deadline.status === 'filed'
                    ? `Filed · ${deadline.title.split(' · ')[0]}`
                    : deadline.title}
            </span>
            {deadline.amount !== null && deadline.status !== 'filed' && (
                <span className="mt-0.5 block text-[13px]">
                    {peso(deadline.amount)}
                </span>
            )}
        </span>
    );
}
