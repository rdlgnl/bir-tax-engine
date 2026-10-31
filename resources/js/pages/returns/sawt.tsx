import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Step, StepList } from '@/components/step-list';
import { Button } from '@/components/ui/button';
import { peso, quarterMonths } from '@/lib/format';
import { profile } from '@/routes';
import { show as complianceShow } from '@/routes/compliance';
import { show as prepare } from '@/routes/returns';
import { exportMethod } from '@/routes/returns/sawt';
import type { SawtRow } from '@/types';

type Props = {
    year: number;
    quarter: number;
    form: string;
    rows: SawtRow[];
    totals: { clients: number; income: number; withheld: number };
    fileName: string | null;
};

export default function Sawt({
    year,
    quarter,
    form,
    rows,
    totals,
    fileName,
}: Props) {
    const back = prepare([year, quarter, form]).url;
    const exportUrl = exportMethod([year, quarter]).url;
    const missing = rows.filter((row) => !row.certificate);

    return (
        <>
            <Head title={`SAWT · Q${quarter} ${year}`} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={back}
                    aria-label={`Back to ${form}`}
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Withholding summary (SAWT)
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {quarter === 4 ? year : `Q${quarter} ${year}`} · attach
                        to your {form}
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <SectionCard
                        title={`2307s you're claiming · ${quarterMonths(quarter)}`}
                    >
                        {rows.length === 0 ? (
                            <p className="py-2 text-sm text-muted-foreground">
                                No client withheld tax this quarter, so there's
                                nothing to attach.
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
                                                Client
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2"
                                            >
                                                TIN
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2 text-right"
                                            >
                                                Income
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-1.5 py-2 text-right"
                                            >
                                                Withheld
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((row) => (
                                            <tr
                                                key={row.seq}
                                                className="border-b border-divider"
                                            >
                                                <td className="px-1.5 py-3">
                                                    {row.payor}
                                                </td>
                                                <td className="px-1.5 py-3">
                                                    {row.tin ?? (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-1.5 py-3 text-right">
                                                    {peso(row.income)}
                                                </td>
                                                <td className="px-1.5 py-3 text-right font-bold">
                                                    {peso(row.withheld)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </SectionCard>

                    {rows.length > 0 && (
                        <SectionCard title="Total">
                            <dl className="text-[15px] tabular-nums">
                                <div className="flex justify-between border-b border-divider py-3">
                                    <dt>Clients</dt>
                                    <dd className="font-bold">
                                        {totals.clients}
                                    </dd>
                                </div>
                                <div className="flex justify-between border-b border-divider py-3">
                                    <dt>Income payments</dt>
                                    <dd className="font-bold">
                                        {peso(totals.income)}
                                    </dd>
                                </div>
                                <div className="flex items-center justify-between pt-3">
                                    <dt className="font-bold">
                                        Tax withheld (credit on the return)
                                    </dt>
                                    <dd className="text-xl font-extrabold">
                                        {peso(totals.withheld)}
                                    </dd>
                                </div>
                            </dl>
                        </SectionCard>
                    )}

                    {missing.map((row) => (
                        <Note
                            key={row.seq}
                            tone="warning"
                            title={`${row.payor}'s 2307 isn't in yet`}
                        >
                            You can list it now; keep asking for the
                            certificate. Without it the credit can be
                            questioned.{' '}
                            <Link
                                href={complianceShow(quarter).url}
                                className="font-bold underline"
                            >
                                Track it
                            </Link>
                        </Note>
                    ))}
                </div>

                <div className="flex flex-col gap-4">
                    <DownloadCard
                        href={exportUrl}
                        title="Export SAWT file"
                        detail={
                            fileName
                                ? `${fileName} · data file in the BIR's format, ready to submit with your return.`
                                : "Data file in the BIR's format, ready to submit with your return."
                        }
                        disabled={fileName === null || rows.length === 0}
                    />
                    {fileName === null && (
                        <Note tone="warning" title="Your TIN is missing">
                            The file is named by your TIN.{' '}
                            <Link
                                href={profile().url}
                                className="font-bold underline"
                            >
                                Add it to your profile
                            </Link>
                        </Note>
                    )}

                    <SectionCard title="How to submit">
                        <StepList>
                            <Step n={1} title="Export the file">
                                Name and format come from your rule pack
                            </Step>
                            <Step
                                n={2}
                                title="Submit it electronically with the return"
                            >
                                Through the BIR's e-submission channel
                            </Step>
                            <Step n={3} title="Keep the acknowledgment">
                                Keep it with the filed quarter
                            </Step>
                        </StepList>
                    </SectionCard>

                    <Note title="Who needs a SAWT">
                        Anyone claiming tax withheld by clients (2307s) on a
                        1701Q, 1701, 2550Q or similar return.
                    </Note>

                    <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-1">
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <Link href={back}>Back</Link>
                        </Button>
                        {fileName !== null && rows.length > 0 ? (
                            <Button
                                asChild
                                className="h-12 rounded-xl text-[15px] font-bold"
                            >
                                <a href={exportUrl} download>
                                    Export SAWT
                                </a>
                            </Button>
                        ) : (
                            <Button
                                disabled
                                className="h-12 rounded-xl text-[15px] font-bold"
                            >
                                Export SAWT
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
