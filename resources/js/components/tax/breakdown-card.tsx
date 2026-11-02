import { Link } from '@inertiajs/react';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import {
    formatDate,
    minusPeso,
    monthLong,
    peso,
    periodLabel,
} from '@/lib/format';
import { cn } from '@/lib/utils';
import { show as prepare } from '@/routes/returns';
import { compare } from '@/routes/tax';
import { index as transactionsIndex } from '@/routes/transactions';
import type { QuarterResult, YearResult } from '@/types';

type Props = {
    year: YearResult;
    quarter: QuarterResult;
    purelySelfEmployed: boolean;
};

/** "How we calculated it": every line of the cumulative computation. */
export function BreakdownCard({ year, quarter, purelySelfEmployed }: Props) {
    const eightPercent = quarter.regime === 'eight_percent';
    const priorQuarters =
        quarter.quarter === 1 ? null : `Q1–Q${quarter.quarter - 1}`;
    const payable = quarter.status === 'due' || quarter.status === 'overdue';

    return (
        <SectionCard title="How we calculated it">
            <dl className="text-[15px]">
                <Line
                    label={`Gross receipts, ${periodLabel(quarter.quarter)}`}
                    value={peso(quarter.gross)}
                />
                {eightPercent ? (
                    purelySelfEmployed && (
                        <Line
                            label={
                                <>
                                    Less: first ₱250,000{' '}
                                    <span className="text-[13px] text-muted-foreground">
                                        (purely self-employed)
                                    </span>
                                </>
                            }
                            value={minusPeso(quarter.exempt_amount)}
                        />
                    )
                ) : (
                    <Line
                        label={
                            quarter.deduction_method === 'itemized'
                                ? 'Less: expenses with invoices'
                                : 'Less: flat 40% deduction'
                        }
                        value={minusPeso(quarter.deduction)}
                    />
                )}
                <Line
                    label="Taxable amount"
                    value={peso(quarter.taxable)}
                    strong
                    topRule
                />
                <Line
                    label="Rate"
                    value={eightPercent ? '× 8%' : 'Graduated table'}
                />
                <Line
                    label="Tax so far this year"
                    value={peso(quarter.income_tax)}
                    strong
                />
                {priorQuarters && (
                    <Line
                        label={`Less: paid for ${priorQuarters}`}
                        value={minusPeso(quarter.prior_payments)}
                        credit
                    />
                )}
                <Line
                    label={
                        <>
                            Less: withheld by clients{' '}
                            {quarter.withheld_unconfirmed > 0 && (
                                <span className="text-[13px] text-warning">
                                    ({peso(quarter.withheld_unconfirmed)} needs
                                    a 2307)
                                </span>
                            )}
                        </>
                    }
                    value={minusPeso(quarter.withheld)}
                    credit
                />
                {quarter.excess_credit > 0 && (
                    <Line
                        label="Extra credit, carried to the next return"
                        value={peso(quarter.excess_credit)}
                    />
                )}
            </dl>

            {quarter.unpaid_earlier > 0 && quarter.status !== 'filed' && (
                <p className="mt-3 rounded-xl bg-warning-soft px-3.5 py-2.5 text-sm text-warning">
                    Includes {peso(quarter.unpaid_earlier)} from an earlier
                    quarter that wasn't paid. Pay that return, with its
                    penalties, and this one drops by as much.
                </p>
            )}

            <div className="mt-4 flex items-center justify-between gap-4 rounded-2xl bg-ink px-[18px] py-4 text-ink-foreground">
                <div>
                    <p className="text-[15px] font-bold">
                        {quarter.status === 'filed'
                            ? `Filed ${formatDate(quarter.filed_on ?? quarter.due_date)}`
                            : quarter.status === 'open'
                              ? `Estimate so far · due ${formatDate(quarter.due_date, 'short')}`
                              : `To pay by ${formatDate(quarter.due_date, 'short')}`}
                    </p>
                    <p className="text-xs text-slate-300">
                        BIR Form {quarter.form}
                    </p>
                </div>
                <p className="text-3xl font-extrabold">
                    {peso(
                        quarter.status === 'filed'
                            ? (quarter.amount_paid ?? quarter.due)
                            : quarter.due,
                    )}
                </p>
            </div>

            {quarter.percentage_tax !== null && (
                <p className="mt-3 text-sm text-muted-foreground">
                    Plus percentage tax for this quarter:{' '}
                    <strong className="text-foreground">
                        {peso(quarter.percentage_tax)}
                    </strong>{' '}
                    (
                    {year.switched?.quarter === quarter.quarter
                        ? `on your sales from January to ${monthLong(year.switched.month)}`
                        : `3% of ${peso(quarter.quarter_gross)}`}
                    , Form 2551Q).
                </p>
            )}

            <div className="mt-4 flex flex-wrap gap-2.5">
                {payable && (
                    <Button
                        asChild
                        className="h-12 rounded-xl px-[18px] text-[15px] font-bold"
                    >
                        <Link
                            href={
                                prepare([
                                    year.year,
                                    quarter.quarter,
                                    quarter.form,
                                ]).url
                            }
                        >
                            Prepare {quarter.form}
                        </Link>
                    </Button>
                )}
                <Button
                    asChild
                    variant="outline"
                    className="h-12 rounded-xl px-[18px] text-[15px] font-bold"
                >
                    <Link
                        href={
                            transactionsIndex({ query: { year: year.year } })
                                .url
                        }
                    >
                        Where {peso(quarter.gross)} came from
                    </Link>
                </Button>
                <Button
                    asChild
                    variant="outline"
                    className="h-12 rounded-xl px-[18px] text-[15px] font-bold"
                >
                    <Link href={compare().url}>What if?</Link>
                </Button>
            </div>
        </SectionCard>
    );
}

function Line({
    label,
    value,
    strong,
    credit,
    topRule,
}: {
    label: React.ReactNode;
    value: string;
    strong?: boolean;
    credit?: boolean;
    topRule?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex items-baseline justify-between gap-4 border-b border-divider py-3 last:border-0',
                topRule && 'border-t-2 border-t-ink',
            )}
        >
            <dt className={cn(strong && 'font-bold')}>{label}</dt>
            <dd
                className={cn(
                    'font-bold whitespace-nowrap',
                    credit && 'text-success',
                )}
            >
                {value}
            </dd>
        </div>
    );
}
