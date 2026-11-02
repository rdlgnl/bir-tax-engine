import { Head, Link } from '@inertiajs/react';
import { Note } from '@/components/note';
import { BreakdownCard } from '@/components/tax/breakdown-card';
import { QuarterTable } from '@/components/tax/quarter-table';
import { WhyPanel } from '@/components/tax/why-panel';
import { formatDate, monthLong, peso, periodLabel } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/tax';
import type { Explanation, RulePack, YearResult } from '@/types';

type Props = {
    year: number;
    quarter: 1 | 2 | 3 | 4;
    result: YearResult;
    explanation: Explanation;
    methodLockedOn: string | null;
    regimeLabel: string | null;
    purelySelfEmployed: boolean;
    rulePack: RulePack;
};

export default function TaxShow({
    year,
    quarter,
    result,
    explanation,
    regimeLabel,
    purelySelfEmployed,
    rulePack,
}: Props) {
    const current = result.quarters[quarter];
    const returnName = quarter === 4 ? 'Annual return' : `Q${quarter} return`;

    return (
        <>
            <Head title="My tax" />

            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p className="text-[15px] text-muted-foreground">
                        My tax · {returnName} ({periodLabel(quarter)} {year})
                    </p>
                    <h1 className="text-[26px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Income tax:{' '}
                        {peso(
                            current.status === 'filed'
                                ? (current.amount_paid ?? current.due)
                                : current.due,
                        )}{' '}
                        {current.status === 'filed'
                            ? 'paid'
                            : `due ${formatDate(current.due_date)}`}
                    </h1>
                </div>
                <div className="flex items-center gap-3">
                    <span className="rounded-full bg-accent px-3 py-1.5 text-[13px] font-bold text-accent-foreground">
                        {current.status === 'filed'
                            ? `Filed ${formatDate(current.filed_on!)}`
                            : 'Estimate until filed'}
                    </span>
                    <nav
                        aria-label="Quarter"
                        className="flex rounded-xl bg-[#E9ECF0] p-1"
                    >
                        {([1, 2, 3, 4] as const).map((q) => (
                            <Link
                                key={q}
                                href={show(q).url}
                                preserveScroll
                                aria-current={
                                    q === quarter ? 'page' : undefined
                                }
                                className={cn(
                                    'rounded-[10px] px-3.5 py-2 text-[13px] font-bold text-slate-700',
                                    q === quarter &&
                                        'bg-card text-foreground shadow-sm',
                                )}
                            >
                                {q === 4 ? 'Year' : `Q${q}`}
                            </Link>
                        ))}
                    </nav>
                </div>
            </header>

            {result.switched && (
                <div className="mb-5">
                    <Note
                        tone="warning"
                        title={`Your ${year} sales passed ₱3,000,000 in ${monthLong(result.switched.month)}`}
                    >
                        From Q{result.switched.quarter} your income tax is on
                        graduated rates, with expenses that have invoices
                        deducted, and the 8% you paid before is credited.
                        Percentage tax on your sales from January to{' '}
                        {monthLong(result.switched.month)} goes on your Q
                        {result.switched.quarter} 2551Q, with no penalty if paid
                        on time. Sales from{' '}
                        {formatDate(result.switched.vat_from, 'long')} are
                        subject to 12% VAT, on a 2550Q each quarter: register
                        for VAT by{' '}
                        {formatDate(result.switched.register_by, 'long')}. A
                        sale recorded without VAT has 12% added to it.
                    </Note>
                </div>
            )}

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <div className="flex flex-col gap-5">
                    <BreakdownCard
                        year={result}
                        quarter={current}
                        purelySelfEmployed={purelySelfEmployed}
                    />
                    <QuarterTable
                        quarters={Object.values(result.quarters)}
                        selected={quarter}
                    />
                </div>
                <WhyPanel
                    explanation={explanation}
                    regimeLabel={regimeLabel}
                    rulePack={rulePack}
                />
            </div>
        </>
    );
}
