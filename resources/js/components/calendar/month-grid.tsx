import { DeadlineChip, chipTone } from '@/components/calendar/deadline-chip';
import { cn } from '@/lib/utils';
import type { Deadline } from '@/types';

type Props = {
    month: string;
    today: string;
    deadlines: Deadline[];
    holidays: { date: string; name: string }[];
};

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function iso(year: number, month: number, day: number): string {
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

export function MonthGrid({ month, today, deadlines, holidays }: Props) {
    const [year, monthNumber] = month.split('-').map(Number);
    const leading = new Date(year, monthNumber - 1, 1).getDay();
    const days = new Date(year, monthNumber, 0).getDate();
    const holidayOn = Object.fromEntries(holidays.map((h) => [h.date, h.name]));

    return (
        <div
            className="grid grid-cols-7 gap-1.5 sm:gap-2"
            role="grid"
            aria-label="Deadlines this month"
        >
            {weekdays.map((day) => (
                <div
                    key={day}
                    role="columnheader"
                    className="pb-1 text-center text-xs font-extrabold tracking-[0.4px] text-slate-700 uppercase"
                >
                    <abbr title={day} className="no-underline">
                        <span className="sm:hidden">{day[0]}</span>
                        <span className="hidden sm:inline">{day}</span>
                    </abbr>
                </div>
            ))}

            {Array.from({ length: leading }, (_, i) => (
                <div key={`blank-${i}`} aria-hidden />
            ))}

            {Array.from({ length: days }, (_, i) => {
                const day = i + 1;
                const date = iso(year, monthNumber, day);
                const weekend =
                    (leading + i) % 7 === 0 || (leading + i) % 7 === 6;
                const due = deadlines.filter((d) => d.date === date);
                const moved = deadlines.filter(
                    (d) => d.statutory_date === date && d.date !== date,
                );
                const isToday = date === today;
                const urgent = due.some(
                    (d) => d.status === 'due' || d.status === 'overdue',
                );

                return (
                    <div
                        key={date}
                        role="gridcell"
                        aria-current={isToday ? 'date' : undefined}
                        className={cn(
                            'flex min-h-14 flex-col gap-1 rounded-xl border p-1.5 sm:min-h-24 sm:p-2',
                            weekend || holidayOn[date]
                                ? 'bg-muted/70'
                                : 'bg-card',
                            urgent && 'border-2 border-primary',
                            isToday && !urgent && 'border-2 border-ink',
                        )}
                    >
                        <span
                            className={cn(
                                'text-[13px] font-bold',
                                (weekend || holidayOn[date]) &&
                                    'text-slate-500',
                            )}
                        >
                            {day}
                        </span>
                        {holidayOn[date] && (
                            <span className="hidden text-[11px] leading-tight text-slate-600 sm:block">
                                {holidayOn[date]}
                            </span>
                        )}
                        {moved.map((d) => (
                            <span
                                key={d.title}
                                className="hidden text-[11px] leading-tight text-slate-600 sm:block"
                            >
                                Statutory due date, moved to the next working
                                day
                            </span>
                        ))}
                        <div className="hidden flex-col gap-1 sm:flex">
                            {due.map((d) => (
                                <DeadlineChip key={d.title} deadline={d} />
                            ))}
                        </div>
                        <div className="flex gap-1 sm:hidden" aria-hidden>
                            {due.map((d) => (
                                <span
                                    key={d.title}
                                    className={cn(
                                        'size-2 rounded-full',
                                        chipTone(d),
                                    )}
                                />
                            ))}
                        </div>
                        {due.length > 0 && (
                            <span className="sr-only">
                                {due.map((d) => d.title).join(', ')}
                            </span>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
