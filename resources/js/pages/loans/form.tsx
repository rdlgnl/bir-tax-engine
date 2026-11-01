import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate } from '@/lib/format';
import { pdf, show } from '@/routes/loans';
import type { Loan } from '@/types';

type Props = { loan: Loan; edition: string; atc: string; rate: string };

export default function LoanForm({ loan, edition, atc, rate }: Props) {
    const { taxpayer } = usePage().props;
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const signed = new Date(`${loan.signed_on}T00:00:00`);
    const month = new Intl.DateTimeFormat('en-US', {
        month: 'long',
        year: 'numeric',
    }).format(signed);
    const monthName = new Intl.DateTimeFormat('en-US', {
        month: 'long',
    }).format(signed);
    const penalties = loan.dst.penalties;
    const toCheck = [
        {
            key: 'amount',
            label: 'The loan amount and signing date are right',
        },
        { key: 'lender', label: "The lender hasn't already filed it" },
    ];

    return (
        <>
            <Head title="Filled Form 2000" />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={show(loan.id).url}
                        aria-label="Back to the loan"
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            Your filled Form 2000
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Official BIR form · {month} · DST · filled on this
                            computer
                        </p>
                    </div>
                </div>
                <div className="flex gap-2.5">
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 rounded-xl font-bold"
                    >
                        <a
                            href={pdf(loan.id, { query: { print: 1 } }).url}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Printer className="size-4" aria-hidden />
                            Print
                        </a>
                    </Button>
                    <Button asChild className="h-11 rounded-xl font-bold">
                        <a href={pdf(loan.id).url} download>
                            <Download className="size-4" aria-hidden />
                            Download PDF
                        </a>
                    </Button>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
                <PaperForm
                    form="2000"
                    title="Documentary Stamp Tax Declaration/Return"
                    edition={edition}
                    parts={[
                        {
                            title: 'Part I · Background information',
                            split: true,
                            rows: [
                                { label: 'For the month', value: month },
                                {
                                    label: 'Taxpayer name',
                                    value: (
                                        taxpayer?.full_name ?? ''
                                    ).toUpperCase(),
                                },
                                {
                                    label: 'Other party',
                                    value: loan.lender.toUpperCase(),
                                },
                                { label: 'Party is the', value: 'Creditor' },
                            ],
                        },
                        {
                            title: 'Part II · Computation of tax',
                            rows: [
                                { label: 'ATC', value: atc },
                                {
                                    label: 'Document',
                                    value: `Loan agreement, signed ${formatDate(loan.signed_on)}`,
                                },
                                {
                                    label: 'Tax base',
                                    value: formAmount(loan.amount),
                                },
                                { label: 'Rate', value: rate },
                                {
                                    label: 'Tax due',
                                    value: formAmount(loan.dst.tax),
                                },
                                ...(penalties
                                    ? [
                                          {
                                              label: 'Penalties',
                                              value: formAmount(
                                                  penalties.total,
                                              ),
                                          },
                                      ]
                                    : []),
                                {
                                    label: 'Tax payable',
                                    value: formAmount(loan.dst.total),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    <Note
                        tone={loan.dst.status === 'overdue' ? 'danger' : 'info'}
                        title={`File by ${formatDate(loan.dst.due_date, 'full')}`}
                    >
                        5 days after {monthName} ends. You and the lender are
                        both responsible, so agree who files.
                    </Note>

                    {loan.dst.status !== 'filed' && (
                        <SectionCard>
                            <h2 className="mb-3 text-lg font-extrabold">
                                Check before you sign
                            </h2>
                            {toCheck.map((check) => (
                                <label
                                    key={check.key}
                                    className="flex items-center gap-3 py-1.5 text-[15px]"
                                >
                                    <Checkbox
                                        checked={checks[check.key] ?? false}
                                        onCheckedChange={(c) =>
                                            setChecks({
                                                ...checks,
                                                [check.key]: c === true,
                                            })
                                        }
                                    />
                                    {check.label}
                                </label>
                            ))}
                            <p className="mt-2 text-[13px] text-warning">
                                If the lender was a bank, it usually files DST
                                itself. Don't pay twice.
                            </p>
                        </SectionCard>
                    )}

                    <SectionCard>
                        <dl className="divide-y divide-divider text-sm">
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Form version
                                </dt>
                                <dd className="text-right font-bold">
                                    2000 · {edition}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Keep with
                                </dt>
                                <dd className="text-right font-bold">
                                    Your copy of the signed loan agreement
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {loan.dst.status !== 'filed' && (
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl border-2 border-primary text-[15px] font-bold text-primary"
                        >
                            <Link href={show(loan.id).url}>
                                I've filed it · mark as filed
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
