import { Head, Link } from '@inertiajs/react';
import { CalendarPlus, ChevronLeft, ChevronRight } from 'lucide-react';
import { ComingUp } from '@/components/calendar/coming-up';
import { DeadlineChip } from '@/components/calendar/deadline-chip';
import { HolidayList } from '@/components/calendar/holiday-list';
import type { HolidayRow } from '@/components/calendar/holiday-list';
import { MonthGrid } from '@/components/calendar/month-grid';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { calendar } from '@/routes';
import { exportMethod } from '@/routes/calendar';
import type { Deadline } from '@/types';

type Props = {
    month: string;
    today: string;
    deadlines: Deadline[];
    holidays: { date: string; name: string }[];
    /** Whether the rule pack has this year's proclaimed holidays. */
    holidaysKnown: boolean;
    /** The year's holidays, with the taxpayer's changes. */
    holidayList: HolidayRow[];
    comingUp: Deadline[];
};

function shift(month: string, by: number): string {
    const [year, m] = month.split('-').map(Number);
    const date = new Date(year, m - 1 + by, 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

export default function CalendarIndex({
    month,
    today,
    deadlines,
    holidays,
    holidaysKnown,
    holidayList,
    comingUp,
}: Props) {
    const label = new Intl.DateTimeFormat('en-US', {
        month: 'long',
        year: 'numeric',
    }).format(
        new Date(Number(month.slice(0, 4)), Number(month.slice(5)) - 1, 1),
    );
    const inMonth = deadlines.filter((d) => d.date.startsWith(month));

    return (
        <>
            <Head title={`Calendar · ${label}`} />

            <header className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                    Calendar
                </h1>
                <div className="flex flex-wrap items-center gap-2.5">
                    <Link
                        href={
                            calendar({ query: { month: shift(month, -1) } }).url
                        }
                        preserveScroll
                        aria-label="Previous month"
                        className="flex size-10 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <h2
                        aria-live="polite"
                        className="min-w-36 text-center text-lg font-extrabold"
                    >
                        {label}
                    </h2>
                    <Link
                        href={
                            calendar({ query: { month: shift(month, 1) } }).url
                        }
                        preserveScroll
                        aria-label="Next month"
                        className="flex size-10 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronRight className="size-5" aria-hidden />
                    </Link>
                    <Button
                        asChild
                        variant="outline"
                        className="h-10 rounded-xl font-bold"
                    >
                        <a href={exportMethod().url} download>
                            <CalendarPlus className="size-4" aria-hidden />
                            Add to my calendar
                        </a>
                    </Button>
                </div>
            </header>

            <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div className="flex flex-col gap-5">
                    <SectionCard className="p-3 sm:p-4">
                        <MonthGrid
                            month={month}
                            today={today}
                            deadlines={deadlines}
                            holidays={holidays}
                        />
                    </SectionCard>

                    <SectionCard title={`In ${label}`} className="sm:hidden">
                        {inMonth.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No deadlines this month.
                            </p>
                        ) : (
                            <ul className="flex flex-col gap-2">
                                {inMonth.map((d) => (
                                    <li
                                        key={d.title}
                                        className="flex items-center gap-3"
                                    >
                                        <span className="w-14 text-sm font-extrabold">
                                            {formatDate(d.date)}
                                        </span>
                                        <span className="flex-1">
                                            <DeadlineChip deadline={d} />
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-5">
                    <ComingUp items={comingUp} today={today} />
                    {!holidaysKnown && (
                        <Note
                            tone="warning"
                            title={`${month.slice(0, 4)} holidays are estimated`}
                        >
                            This year's holidays aren't proclaimed yet, so the
                            calendar uses the ones every year keeps: New Year's,
                            Holy Week, Labor Day, Independence Day, National
                            Heroes Day, All Saints', Christmas and Rizal Day.
                            When the proclamation is out, add its other days
                            below.
                        </Note>
                    )}
                    <HolidayList
                        year={Number(month.slice(0, 4))}
                        rows={holidayList}
                    />
                    <SectionCard title="How dates are set">
                        <p className="text-sm text-slate-700">
                            Weekends and holidays move a deadline to the next
                            working day. The holidays come with the app; when a
                            proclamation adds or moves a day, change the list
                            and the deadlines follow.
                        </p>
                        <p className="mt-2 text-sm text-muted-foreground">
                            "Add to my calendar" downloads the next 12 months of
                            deadlines, each with a reminder a week before.
                        </p>
                    </SectionCard>
                </div>
            </div>
        </>
    );
}
