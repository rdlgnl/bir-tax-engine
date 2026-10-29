import { Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ChecklistCard } from '@/components/home/checklist-card';
import { NextPaymentCard } from '@/components/home/next-payment-card';
import { QuarterCard } from '@/components/home/quarter-card';
import { UpcomingCard } from '@/components/home/upcoming-card';
import { CardLink, SectionCard } from '@/components/section-card';
import { TransactionTable } from '@/components/transactions/transaction-table';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import { index as transactionsIndex } from '@/routes/transactions';
import type {
    Checklist,
    NextPayment,
    QuarterResult,
    TransactionWithEffect,
    UpcomingItem,
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
    upcoming: UpcomingItem[];
    recent: TransactionWithEffect[];
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
                <Button
                    asChild
                    className="hidden h-11 rounded-xl px-5 text-[15px] font-bold lg:inline-flex"
                >
                    <Link href={transactionsIndex().url}>
                        <Plus className="size-5" aria-hidden />
                        Add transaction
                    </Link>
                </Button>
            </header>

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
