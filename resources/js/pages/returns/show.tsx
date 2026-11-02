import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Download } from 'lucide-react';
import { MarkFiledForm } from '@/components/mark-filed-form';
import { Note } from '@/components/note';
import { CopyValues } from '@/components/returns/copy-values';
import { SectionCard } from '@/components/section-card';
import { Step, StepList } from '@/components/step-list';
import { formatDate, peso } from '@/lib/format';
import { show as complianceShow } from '@/routes/compliance';
import { late, preview, sawt, show, slsp } from '@/routes/returns';
import type { ReturnLayout } from '@/types';

type Props = {
    layout: ReturnLayout;
    dueDate: string;
    periodEnded: boolean;
    periodEnd: string;
    missingCertificates: { counterparty: string; amount: number }[];
    otherForms: string[];
};

export default function ReturnShow({
    layout,
    dueDate,
    periodEnded,
    periodEnd,
    missingCertificates,
    otherForms,
}: Props) {
    const name =
        layout.quarter === 4 && !['2551Q', '2550Q'].includes(layout.form)
            ? `${layout.year} annual`
            : `Q${layout.quarter}`;
    const args: [number, number, string] = [
        layout.year,
        layout.quarter,
        layout.form,
    ];

    return (
        <>
            <Head title={`File ${layout.form}`} />

            <header className="mb-6">
                <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                    File your {name} return
                </h1>
                <p className="text-[15px] text-muted-foreground">
                    BIR Form {layout.form} · {layout.method} · due{' '}
                    {formatDate(dueDate, 'short')} · {peso(layout.payable)}
                </p>
            </header>

            {layout.penalties && (
                <div className="mb-5">
                    <Note tone="danger" title="This return is late">
                        Paying today adds {peso(layout.penalties.total)} in
                        surcharge and interest, so {peso(layout.total_payable)}{' '}
                        in all.{' '}
                        <Link
                            href={
                                late([layout.year, layout.quarter, layout.form])
                                    .url
                            }
                            className="font-bold underline"
                        >
                            See the estimate
                        </Link>
                    </Note>
                </div>
            )}

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
                <CopyValues layout={layout} />

                <div className="flex flex-col gap-5">
                    <Link
                        href={preview(args).url}
                        className="flex items-center gap-4 rounded-card bg-ink p-5 text-ink-foreground hover:bg-slate-800"
                    >
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary">
                            <Download className="size-5" aria-hidden />
                        </span>
                        <span>
                            <span className="block text-lg font-extrabold">
                                See your filled {layout.form}
                            </span>
                            <span className="text-sm text-slate-300">
                                Filled from your records, to check and keep. You
                                file it in eBIRForms.
                            </span>
                        </span>
                    </Link>

                    <SectionCard>
                        <StepList>
                            <Step
                                n={1}
                                title={`Open ${layout.form} in eBIRForms`}
                            >
                                Choose the {layout.method} option, as on your Q1
                                return. (eFPS instead, if you're enrolled.)
                            </Step>
                            <Step n={2} title="Copy the values on the left">
                                Then submit and keep the confirmation email.
                            </Step>
                            {layout.form === '2550Q' && (
                                <Step
                                    n={3}
                                    title="Submit your sales and purchases list (SLSP)"
                                >
                                    <Link
                                        href={
                                            slsp([layout.year, layout.quarter])
                                                .url
                                        }
                                        className="block font-bold text-primary hover:underline"
                                    >
                                        See and export the SLSP
                                    </Link>
                                    It backs the input VAT you claim.
                                </Step>
                            )}
                            {!['2551Q', '2550Q'].includes(layout.form) && (
                                <Step
                                    n={3}
                                    title="Attach your 2307 summary (SAWT)"
                                >
                                    <Link
                                        href={
                                            sawt([layout.year, layout.quarter])
                                                .url
                                        }
                                        className="block font-bold text-primary hover:underline"
                                    >
                                        Download SAWT file
                                    </Link>
                                    {missingCertificates.map((m) => (
                                        <span
                                            key={m.counterparty}
                                            className="mt-1 flex items-start gap-1.5 text-warning"
                                        >
                                            <AlertTriangle
                                                className="mt-0.5 size-3.5 shrink-0"
                                                aria-hidden
                                            />
                                            {m.counterparty}'s 2307 is still
                                            missing ({peso(m.amount)}).{' '}
                                            <Link
                                                href={
                                                    complianceShow(
                                                        layout.quarter,
                                                    ).url
                                                }
                                                className="font-bold underline"
                                            >
                                                Track it
                                            </Link>
                                        </span>
                                    ))}
                                </Step>
                            )}
                            <Step
                                n={layout.form === '2551Q' ? 3 : 4}
                                title={`Pay ${peso(layout.payable)}`}
                            >
                                Any bank, e-wallet or channel the BIR accepts.
                                You can file and pay anywhere, not just at your
                                RDO.
                            </Step>
                        </StepList>
                        {otherForms.map((other) => (
                            <p
                                key={other}
                                className="mt-4 rounded-xl bg-accent px-3.5 py-3 text-sm text-accent-foreground"
                            >
                                Also file your {other} for this quarter.{' '}
                                <Link
                                    href={
                                        show([
                                            layout.year,
                                            layout.quarter,
                                            other,
                                        ]).url
                                    }
                                    className="font-bold underline"
                                >
                                    Prepare {other}
                                </Link>
                            </p>
                        ))}
                    </SectionCard>

                    <section className="rounded-card border-2 border-primary bg-card p-5">
                        <h2 className="mb-4 text-lg font-extrabold">
                            Done? Mark it filed and paid
                        </h2>
                        {periodEnded ? (
                            <MarkFiledForm
                                year={layout.year}
                                quarter={layout.quarter}
                                form={layout.form}
                                amountDue={layout.total_payable}
                            />
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                This period ends {formatDate(periodEnd, 'long')}
                                . You can file once it's over; until then these
                                numbers are an estimate.
                            </p>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
