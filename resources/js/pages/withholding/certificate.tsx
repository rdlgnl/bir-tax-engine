import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, Download, Printer } from 'lucide-react';
import { useState } from 'react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { PaperForm } from '@/components/withholding/paper-form';
import { formAmount, formatDate, monthLong, peso } from '@/lib/format';
import { quarter as quarterPage } from '@/routes/withholding';
import { given, pdf } from '@/routes/withholding/certificate';
import type { WithholdingAgentIdentity, WithholdingPayee } from '@/types';

type Props = {
    year: number;
    quarter: number;
    payee: WithholdingPayee;
    agent: WithholdingAgentIdentity;
    edition: string;
};

export default function WithholdingCertificate({
    year,
    quarter,
    payee,
    agent,
    edition,
}: Props) {
    const args: [number, number, string] = [year, quarter, payee.key];
    const first = quarter * 3 - 2;
    const lastDay = new Date(year, quarter * 3, 0).getDate();
    const [checks, setChecks] = useState<Record<string, boolean>>({});
    const [saving, setSaving] = useState(false);
    const rate =
        payee.income > 0
            ? Math.round((payee.withheld / payee.income) * 100)
            : 0;
    const toCheck = [
        {
            key: 'identity',
            label: `${payee.name}'s TIN and name match their COR`,
        },
        {
            key: 'recorded',
            label: `All Q${quarter} payments to ${payee.name} are recorded`,
        },
    ];

    return (
        <>
            <Head title={`2307 for ${payee.name}`} />

            <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3.5">
                    <Link
                        href={quarterPage([year, quarter]).url}
                        aria-label={`Back to Q${quarter} withholding`}
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                            {payee.name}'s 2307
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Certificate you give the person you paid · Q
                            {quarter} {year}
                            {payee.given_on
                                ? ` · given ${formatDate(payee.given_on, 'long')}`
                                : ' · filled on this computer'}
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
                            href={pdf(args, { query: { print: 1 } }).url}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Printer className="size-4" aria-hidden />
                            Print
                        </a>
                    </Button>
                    <Button asChild className="h-11 rounded-xl font-bold">
                        <a href={pdf(args).url} download>
                            <Download className="size-4" aria-hidden />
                            Download PDF
                        </a>
                    </Button>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
                <PaperForm
                    form="2307"
                    title="Certificate of Creditable Tax Withheld at Source"
                    edition={edition}
                    parts={[
                        {
                            title: 'Part I · Payee (who you paid)',
                            split: true,
                            rows: [
                                {
                                    label: 'Period',
                                    value: `${monthLong(first).slice(0, 3)} 1 – ${monthLong(quarter * 3).slice(0, 3)} ${lastDay}, ${year}`,
                                },
                                { label: 'Payee TIN', value: payee.tin ?? '—' },
                                {
                                    label: 'Payee name',
                                    value: payee.name.toUpperCase(),
                                },
                                {
                                    label: 'Registered address',
                                    value: 'Write it in',
                                },
                            ],
                        },
                        {
                            title: 'Part II · Payor (you)',
                            split: true,
                            rows: [
                                { label: 'Payor TIN', value: agent.tin ?? '—' },
                                {
                                    label: 'Payor name',
                                    value: agent.name.toUpperCase(),
                                },
                            ],
                        },
                        {
                            title: 'Part III · Income payments and tax withheld',
                            rows: [
                                { label: 'ATC', value: payee.atc },
                                ...payee.months.map((amount, i) => ({
                                    label: `${['1st', '2nd', '3rd'][i]} month (${monthLong(first + i).slice(0, 3)})`,
                                    value: formAmount(amount),
                                })),
                                {
                                    label: 'Total income payments',
                                    value: formAmount(payee.income),
                                },
                                {
                                    label: `Tax withheld (${rate}%)`,
                                    value: formAmount(payee.withheld),
                                    total: true,
                                },
                            ],
                        },
                    ]}
                />

                <div className="flex flex-col gap-5">
                    <Note title="You must give this certificate">
                        You withheld tax from {payee.name}, so they need this
                        2307 to claim the {peso(payee.withheld)} against their
                        own tax.
                    </Note>

                    {!payee.given_on && (
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
                                The app doesn't keep {payee.name}'s address;
                                write it in on the printed form.
                            </p>
                            {payee.tin === null && (
                                <p className="mt-2 text-[13px] text-warning">
                                    {payee.name}'s TIN is missing. Add it to
                                    their payments so it prints on the form.
                                </p>
                            )}
                        </SectionCard>
                    )}

                    <SectionCard>
                        <dl className="divide-y divide-divider text-sm">
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Form version
                                </dt>
                                <dd className="text-right font-bold">
                                    2307 · {edition}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2">
                                <dt className="text-muted-foreground">
                                    Also needed
                                </dt>
                                <dd className="text-right">
                                    <Link
                                        href={quarterPage([year, quarter]).url}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        Quarterly 1601-EQ with list of payees
                                    </Link>
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>

                    {payee.given_on ? (
                        <Note tone="success" title="Given">
                            Marked given on {formatDate(payee.given_on, 'long')}
                            .
                        </Note>
                    ) : (
                        <Button
                            variant="outline"
                            disabled={saving}
                            onClick={() =>
                                router.post(given(args).url, undefined, {
                                    preserveScroll: true,
                                    onStart: () => setSaving(true),
                                    onFinish: () => setSaving(false),
                                })
                            }
                            className="h-12 rounded-xl border-2 border-primary text-[15px] font-bold text-primary"
                        >
                            Mark as given to {payee.name}
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
