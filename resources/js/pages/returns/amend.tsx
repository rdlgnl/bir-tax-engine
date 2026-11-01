import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { DownloadCard } from '@/components/download-card';
import { MarkFiledForm } from '@/components/mark-filed-form';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { formatDate, peso } from '@/lib/format';
import { history } from '@/routes';
import { pdf } from '@/routes/returns/amend';
import type { Penalty, ReturnLayout } from '@/types';

type Figures = {
    gross: number;
    reduction: number;
    income_tax: number;
    withheld: number;
    due: number;
};

type Props = {
    amendment: {
        year: number;
        quarter: number;
        form: string;
        number: number;
        filed_on: string;
        due_date: string;
        on_time: boolean;
        reason: string;
        filed: Figures;
        amended: Figures;
        paid: number;
        extra: number;
        overpaid: number;
        penalties: Penalty | null;
    };
    layout: ReturnLayout;
    next: { quarter: number; due: number } | null;
};

const exact = (amount: number) =>
    new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);

export default function Amend({ amendment, layout, next }: Props) {
    const { year, quarter, form, filed, amended, penalties } = amendment;
    const args: [number, number, string] = [year, quarter, form];
    const eightPercent = layout.regime === 'eight_percent';
    const rows: [string, keyof Figures][] = [
        [
            `Sales, ${quarter === 1 ? 'Q1' : `Q1${quarter === 3 ? '–Q3' : ' + Q2'}`}`,
            'gross',
        ],
        [eightPercent ? 'Less: reduction' : 'Less: deductions', 'reduction'],
        [eightPercent ? 'Tax due (8%)' : 'Tax due (graduated)', 'income_tax'],
        ['Less: withheld by clients', 'withheld'],
        [`Tax payable, Q${quarter}`, 'due'],
    ];
    const percent = (rate: number) => `${Math.round(rate * 1000) / 10}%`;

    return (
        <>
            <Head title={`Amend Q${quarter} ${year}`} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={history({ query: { year, quarter, form } }).url}
                    aria-label="Back to history"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Amend Q{quarter}
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        BIR Form {form} · amended return · {layout.method}
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <Note title="Why you're amending">
                        {amendment.reason} We keep the original and build the
                        amended return beside it.
                    </Note>

                    <SectionCard title={`Q${quarter}, original vs amended`}>
                        <table className="w-full text-[15px] tabular-nums">
                            <thead>
                                <tr className="border-b text-xs font-bold text-slate-600">
                                    <th className="py-2 text-left" />
                                    <th scope="col" className="py-2 text-right">
                                        Filed {formatDate(amendment.filed_on)}
                                    </th>
                                    <th scope="col" className="py-2 text-right">
                                        Amended
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map(([label, key]) => (
                                    <tr
                                        key={key}
                                        className="border-b border-divider last:border-0"
                                    >
                                        <td
                                            className={
                                                key === 'due'
                                                    ? 'py-3 font-bold'
                                                    : 'py-3'
                                            }
                                        >
                                            {label}
                                        </td>
                                        <td className="py-3 text-right font-bold">
                                            {peso(filed[key])}
                                        </td>
                                        <td className="py-3 text-right font-bold">
                                            {peso(amended[key])}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </SectionCard>

                    <SectionCard
                        title={
                            amendment.extra > 0
                                ? 'What you pay now (estimate)'
                                : 'What changes'
                        }
                    >
                        {amendment.extra > 0 ? (
                            <dl className="text-[15px] tabular-nums">
                                <div className="flex justify-between gap-3 border-b border-divider py-3">
                                    <dt>Extra tax</dt>
                                    <dd className="font-bold">
                                        {peso(amendment.extra)}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3 border-b border-divider py-3">
                                    <dt>Surcharge</dt>
                                    <dd className="text-right font-bold">
                                        {amendment.on_time
                                            ? `₱0 · Q${quarter} was filed on time`
                                            : exact(penalties?.surcharge ?? 0)}
                                    </dd>
                                </div>
                                {penalties && (
                                    <div className="flex justify-between gap-3 border-b border-divider py-3">
                                        <dt>
                                            Interest (
                                            {percent(penalties.interest_rate)} a
                                            year,{' '}
                                            {formatDate(penalties.due_date)} –{' '}
                                            {formatDate(penalties.pay_on)})
                                        </dt>
                                        <dd className="font-bold">
                                            {exact(penalties.interest)}
                                        </dd>
                                    </div>
                                )}
                                <div className="flex items-center justify-between pt-3">
                                    <dt className="font-bold">
                                        Total if paid{' '}
                                        {formatDate(
                                            penalties?.pay_on ??
                                                amendment.due_date,
                                        )}
                                    </dt>
                                    <dd className="text-2xl font-extrabold">
                                        {exact(layout.total_payable)}
                                    </dd>
                                </div>
                            </dl>
                        ) : (
                            <p className="text-[15px]">
                                {amendment.overpaid > 0
                                    ? `You paid ${peso(amendment.overpaid)} more than the amended return asks. Nothing to pay now; it counts toward your next return.`
                                    : 'Nothing more to pay. The amended return only corrects the figures on file.'}
                            </p>
                        )}
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    {amendment.extra > 0 && amendment.on_time && (
                        <Note title="Why no surcharge">
                            The BIR doesn't charge a surcharge on an amended
                            return when the original was filed on time. Interest
                            still runs from the original due date, at half the
                            usual rate for micro and small taxpayers. A
                            compromise penalty may also apply.
                        </Note>
                    )}
                    {next && (
                        <Note
                            tone="success"
                            title={`Q${next.quarter} is ${peso(next.due)}`}
                        >
                            Its running total takes in the change and the extra
                            you pay for Q{quarter} now, so the two settle each
                            other.
                        </Note>
                    )}
                    <DownloadCard
                        href={pdf(args).url}
                        title={`Download amended ${form}`}
                        detail={`Official form with "Amended: Yes" ticked, filled for you.`}
                    />
                    <SectionCard>
                        <h2 className="mb-3 text-lg font-extrabold">
                            Mark amendment filed
                        </h2>
                        <MarkFiledForm
                            year={year}
                            quarter={quarter}
                            form={form}
                            amendment
                            amountDue={layout.total_payable}
                        />
                    </SectionCard>
                </div>
            </div>
        </>
    );
}
