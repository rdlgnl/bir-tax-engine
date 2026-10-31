import { Head, Link, usePage } from '@inertiajs/react';
import { Bell, Plus } from 'lucide-react';
import { ChecklistCard } from '@/components/home/checklist-card';
import { NextPaymentCard } from '@/components/home/next-payment-card';
import { QuarterCard } from '@/components/home/quarter-card';
import { UpcomingCard } from '@/components/home/upcoming-card';
import { Note } from '@/components/note';
import { CardLink, SectionCard } from '@/components/section-card';
import { TransactionTable } from '@/components/transactions/transaction-table';
import { Button } from '@/components/ui/button';
import { formatDate, peso, plural } from '@/lib/format';
import { backup, notifications, rules } from '@/routes';
import { index as transactionsIndex } from '@/routes/transactions';
import type {
    Checklist,
    NextPayment,
    QuarterResult,
    TransactionWithEffect,
    Deadline,
    YearStatus,
} from '@/types';

type Props = {
    today: string;
    nextPayment: NextPayment;
    focus: QuarterResult;
    yearStatus: YearStatus;
    yearGross: number;
    regime: string | null;
    vatRegistered: boolean;
    checklist: Checklist;
    upcoming: Deadline[];
    recent: TransactionWithEffect[];
    backupReminder: { days_since: number | null } | null;
    unreadNotices: number;
    ruleUpdate: string | null;
};

function greeting(): string {
    const hour = new Date().getHours();

    return hour < 12
        ? 'Good morning'
        : hour < 18
          ? 'Good afternoon'
          : 'Good evening';
}

export default function Home({
    today,
    nextPayment,
    focus,
    yearStatus,
    yearGross,
    regime,
    vatRegistered,
    checklist,
    upcoming,
    recent,
    backupReminder,
    unreadNotices,
    ruleUpdate,
}: Props) {
    const { taxpayer } = usePage().props;
    const limitShare = Math.min(100, (yearGross / 3_000_000) * 100);

    return (
        <>
            <Head title="Home" />

            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p className="text-[15px] text-muted-foreground">
                        {formatDate(today, 'full')}
                    </p>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        {greeting()}, {taxpayer?.first_name}
                    </h1>
                </div>
                <div className="flex items-center gap-2.5">
                    <Link
                        href={notifications().url}
                        aria-label={
                            unreadNotices > 0
                                ? `Notifications, ${unreadNotices} new`
                                : 'Notifications'
                        }
                        className="relative flex size-11 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <Bell className="size-5" aria-hidden />
                        {unreadNotices > 0 && (
                            <span className="absolute -top-1.5 -right-1.5 flex min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-xs font-bold text-primary-foreground">
                                {unreadNotices}
                            </span>
                        )}
                    </Link>
                    <Button
                        asChild
                        className="hidden h-11 rounded-xl px-5 text-[15px] font-bold lg:inline-flex"
                    >
                        <Link href={transactionsIndex().url}>
                            <Plus className="size-5" aria-hidden />
                            Add transaction
                        </Link>
                    </Button>
                </div>
            </header>

            {ruleUpdate && (
                <div className="mb-5">
                    <Note title={`Rule pack ${ruleUpdate} installed`}>
                        New BIR rules came with this update.{' '}
                        <Link
                            href={rules().url}
                            className="font-bold underline"
                        >
                            See what changed
                        </Link>
                    </Note>
                </div>
            )}

            {backupReminder && (
                <div className="mb-5">
                    <Note tone="warning" title="Back up your records">
                        {backupReminder.days_since === null
                            ? "You haven't backed up yet."
                            : `Your last backup was ${plural(backupReminder.days_since, 'day')} ago.`}{' '}
                        They live only on this computer.{' '}
                        <Link
                            href={backup().url}
                            className="font-bold underline"
                        >
                            Back up now
                        </Link>
                    </Note>
                </div>
            )}

            <div className="grid gap-5 lg:grid-cols-3">
                <NextPaymentCard payment={nextPayment} />
                {yearStatus === 'ok' && (
                    <>
                        <QuarterCard
                            quarter={focus}
                            regime={regime}
                            vatRegistered={vatRegistered}
                        />
                        <ChecklistCard checklist={checklist} quarter={focus} />
                    </>
                )}
                {/* Left column stacks the short cards; recent activity fills the rest. */}
                <div className="flex flex-col gap-5">
                    <UpcomingCard items={upcoming} />
                    {regime === 'eight_percent' && (
                        <SectionCard title={`Earned in ${today.slice(0, 4)}`}>
                            <p className="text-3xl font-extrabold">
                                {peso(yearGross)}
                            </p>
                            <div
                                role="meter"
                                aria-valuemin={0}
                                aria-valuemax={100}
                                aria-valuenow={Math.round(limitShare)}
                                aria-label="Share of the ₱3,000,000 limit for the 8% option"
                                className="mt-3 h-2 overflow-hidden rounded-full bg-divider"
                            >
                                <div
                                    className="h-full rounded-full bg-primary"
                                    style={{ width: `${limitShare}%` }}
                                />
                            </div>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {Math.round(limitShare)}% of the ₱3,000,000
                                limit for the 8% option
                            </p>
                        </SectionCard>
                    )}
                </div>

                <SectionCard
                    title="Recent transactions"
                    action={
                        <CardLink href={transactionsIndex().url}>
                            All transactions
                        </CardLink>
                    }
                    className="lg:col-span-2"
                >
                    {recent.length === 0 ? (
                        <div className="flex flex-col items-start gap-3 py-2">
                            <p className="text-sm text-muted-foreground">
                                Nothing recorded yet. Start with the payments
                                you've received this year.
                            </p>
                            <Button asChild variant="outline">
                                <Link href={transactionsIndex().url}>
                                    Add a payment
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <TransactionTable
                            transactions={recent}
                            regime={regime}
                        />
                    )}
                </SectionCard>
            </div>
        </>
    );
}
