import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import { home } from '@/routes';
import { pdf, show } from '@/routes/returns';
import type { Penalty, ReturnLayout } from '@/types';

type Props = {
    layout: ReturnLayout & { penalties: Penalty };
    dueDate: string;
    weekly: number;
};

const percent = (rate: number) => `${Math.round(rate * 1000) / 10}%`;

export default function PayingLate({ layout, dueDate, weekly }: Props) {
    const args: [number, number, string] = [
        layout.year,
        layout.quarter,
        layout.form,
    ];
    const penalty = layout.penalties;
    const reduced = penalty.surcharge_rate < 0.25;
    const what =
        layout.quarter === 4
            ? `${layout.year} annual income tax`
            : `Q${layout.quarter} income tax`;
    const exact = (amount: number) =>
        new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
        }).format(amount);

    return (
        <>
            <Head title={`Paying late · ${layout.form}`} />

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
                        Paying late
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {what} · was due {formatDate(dueDate, 'short')}
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <SectionCard
                        title={`If you pay today · ${formatDate(penalty.pay_on, 'short')} (estimate)`}
                    >
                        <dl className="text-[15px] tabular-nums">
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Tax due</dt>
                                <dd className="font-bold">
                                    {exact(layout.payable)}
                                </dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>
                                    Surcharge ({percent(penalty.surcharge_rate)}
                                    {reduced ? ', micro or small taxpayer' : ''}
                                    )
                                </dt>
                                <dd className="font-bold">
                                    {exact(penalty.surcharge)}
                                </dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>
                                    Interest ({percent(penalty.interest_rate)} a
                                    year × {penalty.days}{' '}
                                    {penalty.days === 1 ? 'day' : 'days'})
                                </dt>
                                <dd className="font-bold">
                                    {exact(penalty.interest)}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between pt-3">
                                <dt className="font-bold">Total</dt>
                                <dd className="text-2xl font-extrabold">
                                    {exact(layout.total_payable)}
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    <SectionCard title="Each week of delay adds">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-[15px] font-bold">
                                    Interest only
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    The surcharge is charged once
                                </p>
                            </div>
                            <p className="font-bold">≈ {peso(weekly)}/week</p>
                        </div>
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    {reduced && (
                        <Note title="Why it's lower than you may have heard">
                            Under the Ease of Paying Taxes Act, micro and small
                            taxpayers pay a {percent(penalty.surcharge_rate)}{' '}
                            surcharge (not 25%) and half the usual interest.
                        </Note>
                    )}
                    <Note tone="warning" title="Estimate only">
                        A compromise penalty may be added by the BIR. Rates come
                        from your rule pack.
                    </Note>
                    <DownloadCard
                        href={pdf(args).url}
                        title={`Download filled ${layout.form}`}
                        detail="Same form; the penalties go in its penalty lines."
                    />
                    <Button
                        asChild
                        className="h-12 rounded-xl bg-destructive text-[15px] font-bold text-white hover:bg-destructive/90"
                    >
                        <Link href={show(args).url}>File and pay now</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
