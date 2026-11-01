import { Link } from '@inertiajs/react';
import { SectionCard } from '@/components/section-card';
import { minusPeso, peso, periodLabel } from '@/lib/format';
import { show as taxShow } from '@/routes/tax';
import type { QuarterResult } from '@/types';

/** "Side business · 2026": the business's tax for someone who also has a job. */
export function SideBusinessCard({
    quarter,
    regime,
    year,
}: {
    quarter: QuarterResult;
    regime: string | null;
    year: number;
}) {
    const eightPercent = regime === 'eight_percent';
    const earlier = quarter.quarter - 1;
    const name = quarter.quarter === 4 ? 'Annual' : `Q${quarter.quarter}`;

    return (
        <SectionCard title={`Side business · ${year}`}>
            <dl className="text-[15px] tabular-nums">
                <div className="flex justify-between border-b border-divider py-2.5">
                    <dt>Earned so far ({periodLabel(quarter.quarter)})</dt>
                    <dd className="font-bold">{peso(quarter.gross)}</dd>
                </div>
                <div className="flex justify-between border-b border-divider py-2.5">
                    <dt>
                        {eightPercent
                            ? '8% tax on all of it'
                            : 'Tax at graduated rates'}
                    </dt>
                    <dd className="font-bold">{peso(quarter.income_tax)}</dd>
                </div>
                {earlier > 0 && (
                    <div className="flex justify-between border-b border-divider py-2.5">
                        <dt>
                            Paid in{' '}
                            {earlier === 1
                                ? 'Q1'
                                : earlier === 2
                                  ? 'Q1 and Q2'
                                  : 'Q1 to Q3'}
                        </dt>
                        <dd className="font-bold text-success">
                            {quarter.prior_payments > 0
                                ? minusPeso(quarter.prior_payments)
                                : peso(0)}
                        </dd>
                    </div>
                )}
                <div className="flex items-center justify-between pt-2.5">
                    <dt className="font-bold">{name} to pay</dt>
                    <dd className="text-xl font-extrabold">
                        {peso(quarter.due)}
                    </dd>
                </div>
            </dl>
            {eightPercent && (
                <p className="mt-3 text-[13px] text-muted-foreground">
                    No ₱250,000 reduction, because you also have a salary.{' '}
                    <Link
                        href={taxShow(quarter.quarter).url}
                        className="font-bold text-primary underline"
                    >
                        Why?
                    </Link>
                </p>
            )}
        </SectionCard>
    );
}
