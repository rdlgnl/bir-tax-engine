import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, FileSpreadsheet } from 'lucide-react';
import { DownloadCard } from '@/components/download-card';
import { MarkFiledForm } from '@/components/mark-filed-form';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import {
    formatDate,
    minusPeso,
    monthLong,
    peso,
    quarterMonths,
} from '@/lib/format';
import { profile, withholding } from '@/routes';
import { annual, certificate, qap } from '@/routes/withholding';
import { form as formPage, pdf } from '@/routes/withholding/quarter';
import type { WithholdingAgentIdentity, WithholdingQuarter } from '@/types';

type Props = WithholdingQuarter & {
    year: number;
    qapFile: string | null;
    annualDue: string;
    agent: WithholdingAgentIdentity;
};

export default function WithholdingQuarterPage({
    year,
    quarter,
    months,
    withheld,
    remitted,
    still_due,
    payees,
    due_date,
    status,
    filed_on,
    qapFile,
    annualDue,
    agent,
}: Props) {
    const args: [number, number] = [year, quarter];
    const remittedMonths = months
        .slice(0, 2)
        .filter((m) => m.status === 'filed')
        .map((m) => monthLong(m.month));
    const notGiven = payees.filter((p) => p.given_on === null);

    return (
        <>
            <Head title={`Q${quarter} withholding · ${year}`} />

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
                        Q{quarter} withholding return
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {agent.name} · BIR Form 1601-EQ
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <section className="rounded-card bg-ink p-6 text-ink-foreground">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="text-[13px] font-extrabold tracking-[0.4px] text-slate-300 uppercase">
                                {status === 'filed'
                                    ? `Q${quarter} filed`
                                    : `Still to remit for Q${quarter}`}
                            </h2>
                            <span className="rounded-full bg-[#FDE68A] px-2.5 py-1 text-xs font-bold text-ink">
                                {status === 'filed' && filed_on
                                    ? `Filed ${formatDate(filed_on)}`
                                    : `Due ${formatDate(due_date)}`}
                            </span>
                        </div>
                        <p className="mt-2 text-5xl font-extrabold tracking-tight tabular-nums">
                            {peso(still_due)}
                        </p>
                        <p className="mt-2 text-[15px] text-slate-300">
                            {monthLong(quarter * 3)} withholding
                            {remittedMonths.length < 2 &&
                                ', plus months not yet marked remitted'}
                            .
                            {remittedMonths.length > 0 &&
                                ` ${remittedMonths.join(' and ')} ${remittedMonths.length === 1 ? 'was' : 'were'} remitted with 0619-E.`}
                        </p>
                    </section>

                    <SectionCard title={`${quarterMonths(quarter)} ${year}`}>
                        <dl className="text-[15px] tabular-nums">
                            {months.map((m) => (
                                <div
                                    key={m.month}
                                    className="flex justify-between border-b border-divider py-3"
                                >
                                    <dt>Withheld in {monthLong(m.month)}</dt>
                                    <dd className="font-bold">
                                        {peso(m.withheld)}
                                    </dd>
                                </div>
                            ))}
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>Total for the quarter</dt>
                                <dd className="font-bold">{peso(withheld)}</dd>
                            </div>
                            <div className="flex justify-between border-b border-divider py-3">
                                <dt>
                                    Less: remitted with 0619-E
                                    {remittedMonths.length > 0 &&
                                        ` (${remittedMonths.join(', ')})`}
                                </dt>
                                <dd className="font-bold text-success">
                                    {remitted > 0
                                        ? minusPeso(remitted)
                                        : peso(0)}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between pt-3">
                                <dt className="font-bold">Still to remit</dt>
                                <dd className="text-xl font-extrabold">
                                    {peso(still_due)}
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    <SectionCard title="List of payees (QAP) · attach">
                        {payees.length === 0 ? (
                            <p className="py-2 text-sm text-muted-foreground">
                                You didn't withhold from anyone this quarter.
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
                                                TIN
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
                                                2307
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {payees.map((payee) => (
                                            <tr
                                                key={payee.key}
                                                className="border-b border-divider"
                                            >
                                                <td className="px-1.5 py-3">
                                                    {payee.name}
                                                </td>
                                                <td className="px-1.5 py-3">
                                                    {payee.tin ?? (
                                                        <span className="text-warning">
                                                            Missing
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-1.5 py-3">
                                                    {payee.atc}
                                                </td>
                                                <td className="px-1.5 py-3 text-right">
                                                    {peso(payee.income)}
                                                </td>
                                                <td className="px-1.5 py-3 text-right font-bold">
                                                    {peso(payee.withheld)}
                                                </td>
                                                <td className="px-1.5 py-3 text-right">
                                                    <Link
                                                        href={
                                                            certificate([
                                                                year,
                                                                quarter,
                                                                payee.key,
                                                            ]).url
                                                        }
                                                        className="font-bold text-primary hover:underline"
                                                    >
                                                        {payee.given_on
                                                            ? 'Given'
                                                            : 'Prepare'}
                                                    </Link>
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
                    <DownloadCard
                        href={pdf(args).url}
                        title="Download filled 1601-EQ"
                        detail="Official BIR form, filled from your payments."
                    />
                    <Link
                        href={formPage(args).url}
                        className="-mt-1 text-center text-sm font-bold text-primary hover:underline"
                    >
                        Preview the filled form
                    </Link>

                    <Note title="The QAP goes with it">
                        The list of payees is filed as a data file through the
                        BIR's submission channel, by the same deadline. We
                        prepare it from your records.
                    </Note>

                    {notGiven.length > 0 && (
                        <Note tone="warning" title="Give 2307s too">
                            Each payee needs a 2307 for the quarter.{' '}
                            {notGiven.map((payee, i) => (
                                <span key={payee.key}>
                                    {i > 0 && ', '}
                                    <Link
                                        href={
                                            certificate([
                                                year,
                                                quarter,
                                                payee.key,
                                            ]).url
                                        }
                                        className="font-bold underline"
                                    >
                                        {payee.name}
                                    </Link>
                                </span>
                            ))}
                        </Note>
                    )}

                    <Note title="At year end">
                        All {year} payees go on the annual 1604-E, due{' '}
                        {formatDate(annualDue, 'long')}.{' '}
                        <Link
                            href={annual(year).url}
                            className="font-bold underline"
                        >
                            Preview
                        </Link>
                    </Note>

                    {qapFile === null && (
                        <Note tone="warning" title="Your TIN is missing">
                            The QAP file is named by your TIN.{' '}
                            <Link
                                href={profile().url}
                                className="font-bold underline"
                            >
                                Add it to your profile
                            </Link>
                        </Note>
                    )}

                    {qapFile !== null && payees.length > 0 ? (
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <a href={qap([year, quarter, 'dat']).url} download>
                                Export QAP file
                            </a>
                        </Button>
                    ) : (
                        <Button
                            disabled
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            Export QAP file
                        </Button>
                    )}
                    {payees.length > 0 && (
                        <a
                            href={qap([year, quarter, 'csv']).url}
                            download
                            className="flex items-center justify-center gap-2 text-sm font-bold text-primary hover:underline"
                        >
                            <FileSpreadsheet className="size-4" aria-hidden />
                            QAP as CSV, for Excel
                        </a>
                    )}

                    {status === 'filed' ? (
                        <Note tone="success" title={`Q${quarter} filed`}>
                            Marked filed
                            {filed_on
                                ? ` on ${formatDate(filed_on, 'long')}`
                                : ''}
                            .
                        </Note>
                    ) : status === 'open' ? (
                        <p className="text-sm text-muted-foreground">
                            The quarter isn't over yet. File after{' '}
                            {monthLong(quarter * 3)} ends, by{' '}
                            {formatDate(due_date, 'long')}.
                        </p>
                    ) : (
                        <SectionCard>
                            <h2 className="mb-3 text-lg font-extrabold">
                                Mark Q{quarter} filed
                            </h2>
                            <MarkFiledForm
                                year={year}
                                quarter={quarter}
                                form="1601-EQ"
                                amountDue={still_due}
                            />
                        </SectionCard>
                    )}
                </div>
            </div>
        </>
    );
}
