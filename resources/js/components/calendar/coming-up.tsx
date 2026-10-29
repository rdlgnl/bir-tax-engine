import { SectionCard } from '@/components/section-card';
import { formatDate, parseDate, peso, plural } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Deadline } from '@/types';

function daysBetween(from: string, to: string): number {
    return Math.round(
        (parseDate(to).getTime() - parseDate(from).getTime()) / 86_400_000,
    );
}

function detail(deadline: Deadline, today: string): string {
    const days = daysBetween(today, deadline.date);
    const when = days === 0 ? 'today' : `in ${plural(days, 'day')}`;

    switch (deadline.kind) {
        case 'decision':
            return '8% or graduated choice locks';
        case 'reminder':
            return 'Reminder · keeps clients at 5%';
        default:
            return deadline.amount === null
                ? `File + pay · amount after the quarter`
                : `File + pay · ${when}`;
    }
}

export function ComingUp({
    items,
    today,
}: {
    items: Deadline[];
    today: string;
}) {
    return (
        <SectionCard title="Coming up">
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Nothing coming up.
                </p>
            ) : (
                <ul className="divide-y divide-divider">
                    {items.map((item) => (
                        <li key={`${item.date}-${item.title}`} className="py-3">
                            <div className="flex items-baseline justify-between gap-3">
                                <p
                                    className={cn(
                                        'text-[15px] font-bold',
                                        item.amount === null &&
                                            item.kind === 'income_tax' &&
                                            'text-primary',
                                    )}
                                >
                                    {formatDate(item.date, 'short')} ·{' '}
                                    {item.title.replace(' · file + pay', '')}
                                </p>
                                {item.amount !== null && (
                                    <span className="font-extrabold">
                                        {peso(item.amount)}
                                    </span>
                                )}
                            </div>
                            <p
                                className={cn(
                                    'text-[13px] text-muted-foreground',
                                    item.kind === 'decision' &&
                                        'font-semibold text-warning',
                                )}
                            >
                                {detail(item, today)}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </SectionCard>
    );
}
