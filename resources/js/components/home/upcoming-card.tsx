import { CardLink, SectionCard } from '@/components/section-card';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import { calendar } from '@/routes';
import type { Deadline } from '@/types';

export function UpcomingCard({ items }: { items: Deadline[] }) {
    return (
        <SectionCard
            title="Upcoming"
            action={<CardLink href={calendar().url}>Calendar</CardLink>}
        >
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No deadlines coming up.
                </p>
            ) : (
                <ul className="divide-y divide-divider">
                    {items.map((item) => (
                        <li
                            key={`${item.date}-${item.title}`}
                            className="grid grid-cols-[64px_1fr_auto] items-center gap-3 py-3 text-sm"
                        >
                            <span className="font-extrabold">
                                {formatDate(item.date)}
                            </span>
                            <span>{item.title}</span>
                            <span
                                className={cn(
                                    'text-right',
                                    item.amount !== null && 'font-extrabold',
                                    item.amount === null &&
                                        'text-[13px] text-muted-foreground',
                                    item.tone === 'action' &&
                                        'font-bold text-warning',
                                )}
                            >
                                {item.amount !== null
                                    ? peso(item.amount)
                                    : item.note}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </SectionCard>
    );
}
