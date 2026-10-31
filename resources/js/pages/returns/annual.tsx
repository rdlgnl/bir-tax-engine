import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { MarkFiledForm } from '@/components/mark-filed-form';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Step, StepList } from '@/components/step-list';
import { Button } from '@/components/ui/button';
import { formatDate, minusPeso, peso } from '@/lib/format';
import { eafs, preview } from '@/routes/returns';
import type { ReturnLayout } from '@/types';

type Props = {
    layout: ReturnLayout;
    dueDate: string;
    periodEnded: boolean;
    periodEnd: string;
    eightPercent: boolean;
    figures: {
        gross: number;
        less: number;
        income_tax: number;
        prior_payments: number;
        withheld: number;
        payable: number;
        overpayment: number;
    };
    certificatesNotIn: { counterparty: string; withheld: number }[];
    scans: number;
    alternativeForm: string | null;
};

export default function AnnualReturn({
    layout,
    dueDate,
    periodEnded,
    periodEnd,
    eightPercent,
    figures,
    certificatesNotIn,
    scans,
    alternativeForm,
}: Props) {
    const args: [number, number, string] = [layout.year, 4, layout.form];
    const estimate = !periodEnded;

    return (
        <>
            <Head title={`${layout.year} annual return`} />

            <header className="mb-6">
                <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                    Your {layout.year} annual return
                </h1>
                <p className="text-[15px] text-muted-foreground">
                    BIR Form {layout.form} · due {formatDate(dueDate, 'short')},{' '}
                    {dueDate.slice(0, 4)}
                </p>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div className="flex flex-col gap-5">
                    <section className="rounded-card bg-ink p-6 text-ink-foreground">
                        <p className="text-[13px] font-extrabold tracking-[0.4px] text-slate-300 uppercase">
                            {figures.overpayment > 0
                                ? `Overpaid for ${layout.year}`
                                : `Still to pay for ${layout.year}`}
                            {estimate && ' (estimate)'}
                        </p>
                        <p className="mt-1 text-[44px] leading-none font-extrabold">
                            {peso(
                                figures.overpayment > 0
                                    ? figures.overpayment
                                    : figures.payable,
                            )}
                        </p>
                        <p className="mt-2 text-sm text-slate-300">
                            {eightPercent
                                ? '8% on the whole year'
                                : 'Graduated rates on the whole year'}
                            , less what you paid each quarter and what clients
                            withheld.
                            {estimate &&
                                ` From your records so far; final after ${formatDate(periodEnd, 'long')}.`}
                        </p>
                    </section>

                    <SectionCard title="How it adds up">
                        <dl className="divide-y divide-divider text-[15px] tabular-nums">
                            <Row
                                label={`Sales, Jan–Dec${estimate ? ' (recorded so far)' : ''}`}
                                value={peso(figures.gross)}
                            />
                            <Row
                                label={
                                    eightPercent
                                        ? 'Less: allowable reduction'
                                        : layout.deduction_method === 'itemized'
                                          ? 'Less: itemized deductions'
                                          : 'Less: optional standard deduction (40%)'
                                }
                                value={minusPeso(figures.less)}
                            />
                            <Row
                                label={`Tax for the year${eightPercent ? ' (8%)' : ''}`}
                                value={peso(figures.income_tax)}
                            />
                            <Row
                                label="Paid with Q1–Q3 returns"
                                value={minusPeso(figures.prior_payments)}
                            />
                            <Row
                                label="Withheld by clients (2307s)"
                                value={minusPeso(figures.withheld)}
                            />
                            <Row
                                label={
                                    figures.overpayment > 0
                                        ? 'Overpaid'
                                        : 'Still to pay'
                                }
                                value={peso(
                                    figures.overpayment > 0
                                        ? figures.overpayment
                                        : figures.payable,
                                )}
                                strong
                            />
                        </dl>
                    </SectionCard>

                    {certificatesNotIn.map((missing) => (
                        <Note
                            key={missing.counterparty}
                            tone="warning"
                            title={`${missing.counterparty}'s 2307 isn't in yet`}
                        >
                            Without it, its {peso(missing.withheld)} can't be
                            claimed and you'd pay{' '}
                            {peso(figures.payable + missing.withheld)}.
                        </Note>
                    ))}

                    <Note title="Q4 has no quarterly return">
                        The annual return covers October to December. That's why
                        the last payment of the year happens in April.
                    </Note>
                </div>

                <div className="flex flex-col gap-4">
                    <Link
                        href={preview(args).url}
                        className="flex items-center gap-4 rounded-card bg-ink p-5 text-ink-foreground hover:bg-slate-800"
                    >
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary">
                            <Download className="size-5" aria-hidden />
                        </span>
                        <span>
                            <span className="block text-lg font-extrabold">
                                Download filled {layout.form}
                            </span>
                            <span className="text-sm text-slate-300">
                                {layout.form === '1701-MS'
                                    ? 'Official BIR form for micro and small taxpayers, filled for you.'
                                    : 'Official BIR form, filled for you.'}
                            </span>
                        </span>
                    </Link>
                    {alternativeForm && (
                        <p className="-mt-1 text-[13px] text-muted-foreground">
                            The BIR also accepts the{' '}
                            <Link
                                href={
                                    preview([layout.year, 4, alternativeForm])
                                        .url
                                }
                                className="font-bold text-primary hover:underline"
                            >
                                {alternativeForm}
                            </Link>
                            ; the {layout.form} is the shortest.
                        </p>
                    )}

                    <SectionCard title="How to file">
                        <StepList>
                            <Step n={1} title="Print 3 copies">
                                Long bond paper, then sign all three
                            </Step>
                            <Step n={2} title="File and pay">
                                With tax to pay, at any accredited bank or
                                online. With nothing to pay, file at any RDO or
                                online.
                            </Step>
                            <Step
                                n={3}
                                title="Upload your 2307s within 15 days"
                            >
                                Through the BIR's eAFS site
                            </Step>
                        </StepList>
                    </SectionCard>

                    {eightPercent && (
                        <Note title="No financial statements needed">
                            On 8%, you don't attach financial statements.
                        </Note>
                    )}

                    {scans > 0 ? (
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <a href={eafs(layout.year).url} download>
                                Bundle 2307s for eAFS
                            </a>
                        </Button>
                    ) : (
                        <p className="text-[13px] text-muted-foreground">
                            Add your 2307 scans in Compliance to bundle them for
                            eAFS.
                        </p>
                    )}

                    <section className="rounded-card border-2 border-primary bg-card p-5">
                        <h2 className="mb-4 text-lg font-extrabold">
                            Done? Mark the annual return filed
                        </h2>
                        {periodEnded ? (
                            <MarkFiledForm
                                year={layout.year}
                                quarter={4}
                                form={layout.form}
                                amountDue={figures.payable}
                            />
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                The year ends {formatDate(periodEnd, 'long')}.
                                You can file once it's over; until then these
                                numbers are an estimate.
                            </p>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

function Row({
    label,
    value,
    strong = false,
}: {
    label: string;
    value: string;
    strong?: boolean;
}) {
    return (
        <div
            className={`flex justify-between gap-3 py-2.5 ${strong ? 'text-lg font-extrabold' : ''}`}
        >
            <dt>{label}</dt>
            <dd className="text-right font-bold">{value}</dd>
        </div>
    );
}
