import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Download } from 'lucide-react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, peso, periodLabel } from '@/lib/format';
import { calendar, home } from '@/routes';
import {
    annual as annualPage,
    edit,
    quarter as quarterPage,
} from '@/routes/corporation';
import type {
    CorporateAnnualEstimate,
    CorporateQuarterTax,
    Corporation,
} from '@/types';

type Explanation = { title: string; body: string };

type Props = {
    corporation: Corporation;
    year: number;
    next: CorporateQuarterTax | null;
    soFar: CorporateQuarterTax | null;
    annual: CorporateAnnualEstimate | null;
    quarters: CorporateQuarterTax[];
    rateNote: Explanation | null;
    mcitNote: Explanation & { applies: boolean };
    mcitRate: number;
    alsoDue: { title: string; detail: string; date: string }[];
};

const percent = (rate: number) => `${Math.round(rate * 100)}%`;

const statusLabel: Record<CorporateQuarterTax['status'], string> = {
    open: 'Quarter not over',
    due: 'To file',
    overdue: 'Late',
    filed: 'Filed',
};

export default function CorporationShow({
    corporation,
    year,
    next,
    soFar,
    annual,
    quarters,
    rateNote,
    mcitNote,
    mcitRate,
    alsoDue,
}: Props) {
    return (
        <>
            <Head title={corporation.name} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={home().url}
                    aria-label="Back to Home"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        {corporation.name}
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Corporation
                        {corporation.vat_registered && ' · VAT-registered'} ·
                        calendar year
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <NextToPay next={next} year={year} />

                    {soFar && (
                        <SectionCard
                            title={`${year} so far (${periodLabel(soFar.quarter)})`}
                        >
                            <dl className="divide-y divide-divider text-[15px]">
                                <Row
                                    label="Sales"
                                    value={peso(soFar.to_date.sales)}
                                />
                                <Row
                                    label="Gross income"
                                    value={peso(soFar.to_date.gross_income)}
                                />
                                <Row
                                    label="Taxable income"
                                    value={peso(soFar.to_date.taxable_income)}
                                />
                                <Row
                                    label={`Income tax (${percent(soFar.rate)})`}
                                    value={peso(soFar.regular_tax)}
                                />
                                {soFar.mcit_applies && (
                                    <Row
                                        label={`Minimum tax check (${percent(mcitRate)})`}
                                        value={
                                            <span className="font-normal text-muted-foreground">
                                                {peso(soFar.mcit)} ·{' '}
                                                {soFar.uses === 'mcit'
                                                    ? 'minimum is higher'
                                                    : 'regular is higher'}
                                            </span>
                                        }
                                    />
                                )}
                            </dl>
                        </SectionCard>
                    )}

                    {alsoDue.length > 0 && (
                        <SectionCard title="Also due">
                            <ul className="divide-y divide-divider">
                                {alsoDue.map((item) => (
                                    <li
                                        key={item.title}
                                        className="flex items-center justify-between gap-3 py-3"
                                    >
                                        <span>
                                            <span className="block text-[15px] font-bold">
                                                {item.title}
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                {item.detail}
                                            </span>
                                        </span>
                                        <span className="text-sm font-bold whitespace-nowrap">
                                            {formatDate(item.date)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </SectionCard>
                    )}

                    <SectionCard title={`${year} quarterly returns`}>
                        <ul className="divide-y divide-divider">
                            {quarters.map((q) => (
                                <li key={q.quarter}>
                                    <Link
                                        href={
                                            quarterPage([year, q.quarter]).url
                                        }
                                        className="flex items-center justify-between gap-3 py-3 hover:bg-muted/50"
                                    >
                                        <span>
                                            <span className="block text-[15px] font-bold">
                                                Q{q.quarter} 1702Q
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                {q.filed_on
                                                    ? `Filed ${formatDate(q.filed_on, 'long')}`
                                                    : q.has_figures
                                                      ? `Due ${formatDate(q.due_date, 'long')}`
                                                      : 'No figures yet'}
                                            </span>
                                        </span>
                                        <span className="flex items-center gap-2 text-sm font-bold">
                                            {statusLabel[q.status]}
                                            <ChevronRight
                                                className="size-4 text-muted-foreground"
                                                aria-hidden
                                            />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    {rateNote && (
                        <Note title={rateNote.title}>{rateNote.body}</Note>
                    )}
                    <Note
                        tone={mcitNote.applies ? 'warning' : 'info'}
                        title={mcitNote.title}
                    >
                        {mcitNote.body}
                    </Note>
                    {annual && (
                        <Link
                            href={annualPage(annual.year).url}
                            className="flex items-center gap-4 rounded-card bg-ink p-5 text-ink-foreground hover:bg-slate-800"
                        >
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary">
                                <Download className="size-5" aria-hidden />
                            </span>
                            <span>
                                <span className="block text-lg font-extrabold">
                                    Annual return{' '}
                                    {annual.estimated && 'estimate '}
                                    (1702-RT)
                                </span>
                                <span className="text-sm text-slate-300">
                                    {annual.filed_on
                                        ? `Filed ${formatDate(annual.filed_on, 'long')}`
                                        : `${peso(annual.payable)} ${annual.payable >= 0 ? 'still to pay' : 'overpaid'} for ${annual.year}, due ${formatDate(annual.due_date, 'long')}`}
                                    .
                                </span>
                            </span>
                        </Link>
                    )}
                    <Button
                        asChild
                        variant="outline"
                        className="h-12 rounded-xl text-[15px] font-bold"
                    >
                        <Link href={calendar().url}>All deadlines</Link>
                    </Button>
                    {next && (
                        <Button
                            asChild
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <Link href={quarterPage([year, next.quarter]).url}>
                                Prepare 1702Q
                            </Link>
                        </Button>
                    )}
                    <Button asChild variant="ghost" className="font-bold">
                        <Link href={edit().url}>Corporation details</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

function NextToPay({
    next,
    year,
}: {
    next: CorporateQuarterTax | null;
    year: number;
}) {
    if (!next) {
        return (
            <section
                aria-label="next to pay"
                className="rounded-card bg-ink p-6 text-ink-foreground"
            >
                <h2 className="mb-3 text-[13px] font-extrabold tracking-[0.6px] text-slate-200">
                    NEXT TO PAY
                </h2>
                <p className="text-2xl font-extrabold">
                    No 1702Q to file right now
                </p>
                <p className="mt-2 text-[15px] text-slate-200">
                    Each quarter's return is due 60 days after it ends.
                </p>
            </section>
        );
    }

    const overdue = next.status === 'overdue';

    return (
        <section
            aria-label="next to pay"
            className="rounded-card bg-ink p-6 text-ink-foreground"
        >
            <div className="mb-3 flex items-center justify-between gap-3">
                <h2 className="text-[13px] font-extrabold tracking-[0.6px] text-slate-200">
                    {overdue ? 'OVERDUE' : 'NEXT TO PAY'}
                </h2>
                <span
                    className={
                        overdue
                            ? 'rounded-full bg-red-600 px-2.5 py-1 text-xs font-extrabold text-white'
                            : 'rounded-full bg-highlight px-2.5 py-1 text-xs font-extrabold text-ink'
                    }
                >
                    Due {formatDate(next.due_date, 'short')}
                </span>
            </div>
            {next.has_figures ? (
                <>
                    <p className="text-5xl font-extrabold tracking-tight sm:text-[56px]">
                        {peso(Math.max(0, next.payable))}
                    </p>
                    <p className="mt-3 text-[15px] text-slate-200">
                        Q{next.quarter} income tax (1702Q) ·{' '}
                        {next.uses === 'mcit'
                            ? 'minimum corporate income tax'
                            : `${percent(next.rate)} regular rate`}
                    </p>
                </>
            ) : (
                <>
                    <p className="text-2xl font-extrabold">
                        Q{next.quarter} {year} 1702Q
                    </p>
                    <p className="mt-2 text-[15px] text-slate-200">
                        Enter the quarter's figures from the books to see what's
                        due.
                    </p>
                </>
            )}
        </section>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex justify-between gap-3 py-3">
            <dt>{label}</dt>
            <dd className="text-right font-bold">{value}</dd>
        </div>
    );
}
