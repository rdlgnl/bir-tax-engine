import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, FileSpreadsheet } from 'lucide-react';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { MarkReportedForm } from '@/components/withholding/mark-reported-form';
import { formatDate, monthLong, peso, todayIso } from '@/lib/format';
import { calendar, profile, withholding } from '@/routes';
import { alphalist } from '@/routes/withholding';
import { form as formPage, pdf } from '@/routes/withholding/annual';
import type {
    WithholdingAgentIdentity,
    WithholdingAnnual,
    WithholdingAnnualPayee,
} from '@/types';

type Props = WithholdingAnnual & {
    alphalistFile: string | null;
    agent: WithholdingAgentIdentity;
};

/** The next remittance still open: a month's 0619-E or a quarter's 1601-EQ. */
function nextRemittance(
    quarters: WithholdingAnnual['quarters'],
): { label: string; due: string } | null {
    for (const q of quarters) {
        for (const m of q.months.slice(0, 2)) {
            if (m.status !== 'filed' && m.withheld > 0 && m.due_date) {
                return { label: monthLong(m.month), due: m.due_date };
            }
        }

        if (q.status !== 'filed' && q.still_due > 0) {
            return { label: `Q${q.quarter} (1601-EQ)`, due: q.due_date };
        }
    }

    return null;
}

function given(payee: WithholdingAnnualPayee): string {
    const count = payee.quarters.filter((q) => q.given_on !== null).length;

    if (payee.quarters.length === 1) {
        return `Q${payee.quarters[0].quarter} ${count ? 'given' : 'pending'}`;
    }

    return `${count} of ${payee.quarters.length}`;
}

export default function WithholdingAnnualPage({
    year,
    quarters,
    payees,
    income,
    withheld,
    remitted,
    still_due,
    due_date,
    status,
    filed_on,
    alphalistFile,
    agent,
}: Props) {
    const open = status === 'open';
    const next = nextRemittance(quarters);
    const lastMonth = Math.min(
        12,
        year < Number(todayIso().slice(0, 4))
            ? 12
            : Number(todayIso().slice(5, 7)),
    );
    const payeeCount = new Set(payees.map((p) => p.key)).size;

    return (
        <>
            <Head title={`1604-E · ${year}`} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={withholding(year).url}
                    aria-label="Back to withholding"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Annual list of payees
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {agent.name} · BIR Form 1604-E · {year}
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <SectionCard
                        title={
                            open
                                ? `${year} so far (Jan–${monthLong(lastMonth).slice(0, 3)})`
                                : `${year}`
                        }
                    >
                        <dl className="text-[15px] tabular-nums">
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Payees</dt>
                                <dd className="font-bold">{payeeCount}</dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Income payments</dt>
                                <dd className="font-bold">{peso(income)}</dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Tax withheld</dt>
                                <dd className="font-bold">{peso(withheld)}</dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Remitted</dt>
                                <dd className="font-bold text-success">
                                    {peso(remitted)}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between pt-3">
                                <dt className="font-bold">
                                    {next
                                        ? `To remit: ${next.label}, by ${formatDate(next.due)}`
                                        : 'Still to remit'}
                                </dt>
                                <dd className="text-xl font-extrabold">
                                    {peso(still_due)}
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    <SectionCard title="Payees">
                        {payees.length === 0 ? (
                            <p className="py-2 text-sm text-muted-foreground">
                                You didn't withhold from anyone in {year}.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm tabular-nums">
                                    <thead>
                                        <tr className="border-b text-left text-xs font-bold text-slate-600">
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2"
                                            >
                                                Payee
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2"
                                            >
                                                ATC
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2 text-right"
                                            >
                                                Income paid
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2 text-right"
                                            >
                                                Withheld
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2 text-right"
                                            >
                                                2307s given
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {payees.map((payee) => (
                                            <tr
                                                key={`${payee.key}-${payee.atc}`}
                                                className="border-b border-divider"
                                            >
                                                <td className="px-1.5 py-3">
                                                    {payee.name}
                                                </td>
                                                <td className="px-1.5 py-3">
                                                    {payee.atc}
                                                </td>
                                                <td className="px-1.5 py-3 text-right">
                                                    {peso(payee.income)}
                                                </td>
                                                <td className="px-1.5 py-3 text-right">
                                                    {peso(payee.withheld)}
                                                </td>
                                                <td className="px-1.5 py-3 text-right font-bold">
                                                    {given(payee)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <Note title="An information return">
                        1604-E has no payment. It lists everyone you withheld
                        from in {year} and must match your 0619-E and 1601-EQ
                        filings.
                    </Note>

                    {status === 'filed' ? (
                        <Note tone="success" title="1604-E filed">
                            Marked filed
                            {filed_on
                                ? ` on ${formatDate(filed_on, 'long')}`
                                : ''}
                            .
                        </Note>
                    ) : (
                        <Note
                            tone={status === 'overdue' ? 'danger' : 'warning'}
                            title={`Due ${formatDate(due_date, 'full')}`}
                        >
                            {open
                                ? "Final once December is remitted. It's on your calendar."
                                : 'The year is closed; file it with the alphalist data file.'}
                        </Note>
                    )}

                    <DownloadCard
                        href={pdf(year).url}
                        title="Download filled 1604-E"
                        detail={
                            open
                                ? 'Draft until the year closes.'
                                : 'Official BIR form, filled from your payments.'
                        }
                    />
                    <Link
                        href={formPage(year).url}
                        className="-mt-1 text-center text-sm font-bold text-primary hover:underline"
                    >
                        Preview the filled form
                    </Link>

                    {alphalistFile === null && (
                        <Note tone="warning" title="Your TIN is missing">
                            The alphalist file is named by your TIN.{' '}
                            <Link
                                href={profile().url}
                                className="font-bold underline"
                            >
                                Add it to your profile
                            </Link>
                        </Note>
                    )}

                    {alphalistFile !== null && payees.length > 0 ? (
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <a href={alphalist([year, 'dat']).url} download>
                                Export alphalist file
                            </a>
                        </Button>
                    ) : (
                        <Button
                            disabled
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            Export alphalist file
                        </Button>
                    )}
                    {payees.length > 0 && (
                        <a
                            href={alphalist([year, 'csv']).url}
                            download
                            className="flex items-center justify-center gap-2 text-sm font-bold text-primary hover:underline"
                        >
                            <FileSpreadsheet className="size-4" aria-hidden />
                            Alphalist as CSV, for Excel
                        </a>
                    )}

                    {status !== 'filed' &&
                        (open ? (
                            <Button
                                asChild
                                className="h-12 rounded-xl text-[15px] font-bold"
                            >
                                <Link href={calendar().url}>
                                    See it on your calendar
                                </Link>
                            </Button>
                        ) : (
                            <SectionCard>
                                <h2 className="mb-3 text-lg font-extrabold">
                                    Filed it? Mark it filed
                                </h2>
                                <MarkReportedForm year={year} form="1604-E" />
                            </SectionCard>
                        ))}
                </div>
            </div>
        </>
    );
}
