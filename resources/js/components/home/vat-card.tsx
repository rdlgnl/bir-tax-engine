import { Link } from '@inertiajs/react';
import { CardLink, SectionCard } from '@/components/section-card';
import { formatDate, minusPeso, peso, plural } from '@/lib/format';
import { show as prepare } from '@/routes/returns';
import type { VatQuarter } from '@/types';

/** VAT this quarter so far, and how last quarter's 2550Q stands. */
export function VatCard({
    current,
    previous,
    year,
}: {
    current: VatQuarter;
    previous: VatQuarter;
    year: number;
}) {
    const previousYear = current.quarter === 1 ? year - 1 : year;

    return (
        <SectionCard
            title={`VAT · Q${current.quarter} so far`}
            action={
                <span className="text-[13px] text-muted-foreground">
                    Due {formatDate(current.due_date)},{' '}
                    {current.due_date.slice(0, 4)}
                </span>
            }
        >
            <dl className="divide-y divide-divider text-[15px] tabular-nums">
                <div className="flex justify-between gap-3 py-2">
                    <dt>VAT on your sales ({peso(current.vatable_sales)})</dt>
                    <dd className="font-bold">{peso(current.output_vat)}</dd>
                </div>
                <div className="flex justify-between gap-3 py-2">
                    <dt>VAT you paid on purchases</dt>
                    <dd className="font-bold">
                        {minusPeso(current.input_vat)}
                    </dd>
                </div>
                {current.carried_over > 0 && (
                    <div className="flex justify-between gap-3 py-2">
                        <dt>Carried over from Q{previous.quarter}</dt>
                        <dd className="font-bold">
                            {minusPeso(current.carried_over)}
                        </dd>
                    </div>
                )}
                <div className="flex justify-between gap-3 py-2 text-lg font-extrabold">
                    <dt>
                        {current.excess > 0
                            ? 'Excess, carried to next quarter'
                            : 'VAT to pay so far'}
                    </dt>
                    <dd>
                        {peso(
                            current.excess > 0
                                ? current.excess
                                : current.payable,
                        )}
                    </dd>
                </div>
            </dl>
            {current.excluded_count > 0 && (
                <p className="mt-2 rounded-xl bg-warning-soft px-3 py-2 text-[13px] text-warning">
                    {plural(current.excluded_count, 'purchase invoice')} can’t
                    be claimed: not in your name.
                </p>
            )}
            <div className="mt-3 flex items-center justify-between gap-3">
                <span className="text-[13px] text-muted-foreground">
                    Q{previous.quarter} VAT (2550Q):{' '}
                    {previous.status === 'filed'
                        ? `filed${previous.filed_on ? ` ${formatDate(previous.filed_on)}` : ''}`
                        : previous.status === 'overdue'
                          ? 'overdue'
                          : `due ${formatDate(previous.due_date)}`}
                    {previous.status !== 'filed' && (
                        <>
                            {' · '}
                            <Link
                                href={
                                    prepare([
                                        previousYear,
                                        previous.quarter,
                                        '2550Q',
                                    ]).url
                                }
                                className="font-bold text-primary hover:underline"
                            >
                                {peso(previous.payable)}
                            </Link>
                        </>
                    )}
                </span>
                <CardLink href={prepare([year, current.quarter, '2550Q']).url}>
                    Prepare 2550Q
                </CardLink>
            </div>
        </SectionCard>
    );
}
