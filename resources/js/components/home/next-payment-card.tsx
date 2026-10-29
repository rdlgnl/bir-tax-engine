import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { MarkFiledDialog } from '@/components/mark-filed-dialog';
import { Button } from '@/components/ui/button';
import { formatDate, peso, plural, quarterMonths } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show as taxShow } from '@/routes/tax';
import { index as transactionsIndex } from '@/routes/transactions';
import type { NextPayment } from '@/types';

/** The dark "Next to pay" card, in every state from the design. */
export function NextPaymentCard({ payment }: { payment: NextPayment }) {
    const quarter = payment.quarter;

    if ((payment.state === 'due' || payment.state === 'overdue') && quarter) {
        const overdue = payment.state === 'overdue';
        const urgent = !overdue && quarter.days_left <= 2;
        const badge = overdue
            ? `${plural(-quarter.days_left, 'day')} late`
            : quarter.days_left === 0
              ? 'Due today'
              : quarter.days_left === 1
                ? 'Due tomorrow'
                : `${quarter.days_left} days left`;
        const what =
            quarter.quarter === 4
                ? `${payment.year} annual income tax`
                : `Q${quarter.quarter} income tax`;

        return (
            <Shell
                label={overdue ? 'OVERDUE' : 'NEXT TO PAY'}
                badge={badge}
                badgeTone={overdue || urgent ? 'danger' : 'highlight'}
            >
                <p className="text-5xl font-extrabold tracking-tight sm:text-[56px]">
                    {peso(quarter.due)}
                </p>
                <p className="mt-3 text-[15px] text-slate-200">
                    {overdue ? (
                        <>
                            Was due {formatDate(quarter.due_date, 'short')}. As
                            a micro taxpayer, the late surcharge is 10% instead
                            of 25%, plus interest at the reduced rate. Filing
                            today keeps it small.
                        </>
                    ) : (
                        <>
                            {what} for{' '}
                            {quarter.quarter === 4
                                ? 'the year'
                                : quarterMonths(quarter.quarter)}{' '}
                            · {quarter.form} · due{' '}
                            {formatDate(quarter.due_date, 'weekday')}
                            {quarter.due_date_moved_reason &&
                                ` (${quarter.due_date_moved_reason})`}
                        </>
                    )}
                </p>
                <div className="mt-5 grid grid-cols-2 gap-2">
                    <Button
                        asChild
                        className="h-12 rounded-xl bg-white px-3 text-sm font-bold text-ink hover:bg-slate-100"
                    >
                        <Link href={taxShow(quarter.quarter).url}>
                            See breakdown
                        </Link>
                    </Button>
                    <MarkFiledDialog
                        year={payment.year}
                        quarter={quarter}
                        trigger={
                            <Button
                                variant="outline"
                                className="h-12 rounded-xl border-slate-500 bg-transparent px-3 text-sm font-bold text-white hover:bg-white/10 hover:text-white"
                            >
                                {overdue ? 'Mark filed' : 'Mark filed & paid'}
                            </Button>
                        }
                    />
                </div>
                <Link
                    href={taxShow(quarter.quarter).url}
                    className="mt-4 inline-block px-1.5 text-sm font-bold text-slate-200 hover:underline"
                >
                    Why this amount?
                </Link>
            </Shell>
        );
    }

    if (payment.state === 'empty') {
        return (
            <Shell label="EMPTY · NEW USER">
                <p className="text-2xl font-extrabold">
                    No income recorded yet
                </p>
                <p className="mt-2 text-[15px] text-slate-200">
                    Based on your estimate, expect about{' '}
                    {peso(payment.estimate ?? 0)} in tax for {payment.year}. Add
                    payments to see the real number.
                </p>
                <Button
                    asChild
                    className="mt-5 h-12 rounded-xl bg-white px-[18px] font-bold text-ink hover:bg-slate-100"
                >
                    <Link href={transactionsIndex().url}>Add a payment</Link>
                </Button>
            </Shell>
        );
    }

    if (payment.state === 'cannot_compute') {
        return (
            <Shell label="CAN'T COMPUTE YET">
                <p className="text-2xl font-extrabold">
                    We can't compute this one yet
                </p>
                <p className="mt-2 text-[15px] text-slate-200">
                    You passed ₱3,000,000. The rule for moving from 8% to
                    graduated mid-year isn't in your rule pack yet, so we won't
                    guess. A rule update will cover it.
                </p>
            </Shell>
        );
    }

    if (payment.state === 'not_applicable') {
        return (
            <Shell label="NOTHING TO FILE">
                <p className="text-2xl font-extrabold">
                    Your employer handles your income tax
                </p>
                <p className="mt-2 text-[15px] text-slate-200">
                    Tax on your salary is withheld through payroll. If you start
                    a business or freelance, update your profile and we'll take
                    it from there.
                </p>
            </Shell>
        );
    }

    return (
        <Shell label="ALL CLEAR">
            <p className="text-2xl font-extrabold">
                {payment.days_until_next !== null
                    ? `Nothing due for ${plural(payment.days_until_next, 'day')}`
                    : 'Nothing due'}
            </p>
            {payment.next_deadline && (
                <p className="mt-2 text-[15px] text-slate-200">
                    Next deadline:{' '}
                    {formatDate(payment.next_deadline, 'weekday')}
                </p>
            )}
        </Shell>
    );
}

function Shell({
    label,
    badge,
    badgeTone = 'highlight',
    children,
}: {
    label: string;
    badge?: string;
    badgeTone?: 'highlight' | 'danger';
    children: ReactNode;
}) {
    return (
        <section
            aria-label={label.toLowerCase()}
            className="rounded-card bg-ink p-6 text-ink-foreground"
        >
            <div className="mb-3 flex items-center justify-between gap-3">
                <h2 className="text-[13px] font-extrabold tracking-[0.6px] text-slate-200">
                    {label}
                </h2>
                {badge && (
                    <span
                        className={cn(
                            'rounded-full px-2.5 py-1 text-xs font-extrabold uppercase',
                            badgeTone === 'danger'
                                ? 'bg-red-600 text-white'
                                : 'bg-highlight text-ink normal-case',
                        )}
                    >
                        {badge}
                    </span>
                )}
            </div>
            {children}
        </section>
    );
}
