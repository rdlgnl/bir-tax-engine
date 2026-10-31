import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { useState } from 'react';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Step, StepList } from '@/components/step-list';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import { calendar } from '@/routes';
import { pdf } from '@/routes/sworn';

type Declaration = {
    year: number;
    name: string;
    tin: string | null;
    rdo: string | null;
    vat: boolean;
    gross: number;
    over_limit: boolean;
    limit: number;
    fees: string;
    rate: number;
    rate_without: number;
    due: string;
};

type Client = { key: string; name: string; rate: number };

type Props = {
    declaration: Declaration;
    clients: Client[];
    paidInFull: string[];
};

export default function Sworn({ declaration: d, clients, paidInFull }: Props) {
    const [selected, setSelected] = useState(clients[0]?.key ?? null);
    const client = clients.find((c) => c.key === selected) ?? clients[0];
    const next = d.year + 1;

    return (
        <>
            <Head title="Sworn declaration" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={calendar().url}
                    aria-label="Back to Calendar"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Sworn declaration
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        For your clients · keeps their withholding at {d.rate}%
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div className="flex flex-col gap-5">
                    {client ? (
                        <section className="rounded-card border bg-card p-6 text-[15px] leading-relaxed">
                            <h2 className="mb-4 text-center text-sm font-extrabold tracking-[0.3px]">
                                INCOME PAYEE'S SWORN DECLARATION OF GROSS
                                RECEIPTS/SALES
                            </h2>
                            <p>
                                I, <Fill>{d.name}</Fill>, of legal age, with TIN{' '}
                                <Fill>{d.tin ?? '[TIN]'}</Fill>, registered with
                                RDO <Fill>{d.rdo ?? '[RDO]'}</Fill> as a{' '}
                                <Fill>{d.vat ? 'VAT' : 'non-VAT'}</Fill>{' '}
                                taxpayer, declare that:
                            </p>
                            <ol className="mt-3 list-decimal space-y-2 pl-5">
                                {d.over_limit ? (
                                    <li>
                                        My gross receipts for {d.year} exceeded{' '}
                                        {peso(d.limit)}.
                                    </li>
                                ) : (
                                    <li>
                                        My gross receipts for {d.year} did not
                                        exceed {peso(d.limit)}. I expect the
                                        same for {next}.
                                    </li>
                                )}
                                <li>
                                    I am giving this declaration to{' '}
                                    <Fill>{client.name.toUpperCase()}</Fill> so
                                    it withholds{' '}
                                    {d.over_limit ? d.rate_without : d.rate}%
                                    from my {d.fees}.
                                </li>
                                {!d.over_limit && (
                                    <li>
                                        I will notify you if my gross receipts
                                        go above {peso(d.limit)} within the
                                        year.
                                    </li>
                                )}
                            </ol>
                            <p className="mt-4 text-sm">
                                Signed: ____________________ · Date:
                                ____________
                            </p>
                            <p className="mt-2 text-sm text-muted-foreground">
                                SUBSCRIBED AND SWORN to before me this ___ day
                                of ________ {next}. (Notary)
                            </p>
                            <p className="mt-4 border-t border-dashed pt-3 text-[13px] text-muted-foreground">
                                Preview is simplified. The PDF follows the
                                declaration in the BIR's withholding regulations
                                (RR 11-2018).
                            </p>
                        </section>
                    ) : (
                        <SectionCard>
                            <p className="text-[15px]">
                                None of your clients withheld tax in {d.year},
                                so you don't need a sworn declaration. If a
                                client starts withholding, give them one before
                                their first payment.
                            </p>
                        </SectionCard>
                    )}

                    {d.over_limit && (
                        <Note
                            tone="warning"
                            title={`Your ${d.year} receipts passed ${peso(d.limit)}`}
                        >
                            Clients withhold {d.rate_without}% from now on. The
                            declaration still tells them so.
                        </Note>
                    )}
                </div>

                <div className="flex flex-col gap-4">
                    <SectionCard
                        title={`One for each client that withholds · due ${formatDate(d.due)}, ${d.due.slice(0, 4)}`}
                    >
                        <ul className="divide-y divide-divider">
                            {clients.map((c) => (
                                <li
                                    key={c.key}
                                    className="flex items-center justify-between gap-3 py-3"
                                >
                                    <button
                                        type="button"
                                        onClick={() => setSelected(c.key)}
                                        aria-pressed={c.key === client?.key}
                                        className={cn(
                                            'flex-1 text-left',
                                            c.key === client?.key &&
                                                'font-bold',
                                        )}
                                    >
                                        <span className="block text-[15px]">
                                            {c.name}
                                        </span>
                                        <span className="text-[13px] font-normal text-muted-foreground">
                                            Withholds {c.rate}%
                                        </span>
                                    </button>
                                    <a
                                        href={pdf([d.year, c.key]).url}
                                        download
                                        className="text-sm font-bold text-primary hover:underline"
                                    >
                                        PDF
                                    </a>
                                </li>
                            ))}
                            {paidInFull.length > 0 && (
                                <li className="flex justify-between gap-3 py-3">
                                    <span>
                                        <span className="block text-[15px]">
                                            {paidInFull.join(', ')}
                                        </span>
                                        <span className="text-[13px] text-muted-foreground">
                                            Pay you in full; not needed unless
                                            they start withholding
                                        </span>
                                    </span>
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                </li>
                            )}
                        </ul>
                    </SectionCard>

                    <SectionCard title="What to do">
                        <StepList>
                            <Step n={1} title="Download and print">
                                One copy per client
                            </Step>
                            <Step n={2} title="Sign in front of a notary">
                                A documentary stamp may be needed on the
                                notarized copy
                            </Step>
                            <Step n={3} title="Give it with a copy of your COR">
                                By {formatDate(d.due)}, or before a new client's
                                first payment
                            </Step>
                        </StepList>
                    </SectionCard>

                    <Note tone="warning" title="Without it">
                        Clients must withhold {d.rate_without}% instead of{' '}
                        {d.rate}%. You still get it back as a credit, but your
                        cash is tied up until you file.
                    </Note>

                    {clients.length > 0 && (
                        <DownloadCard
                            href={pdf({ year: d.year }).url}
                            title={
                                clients.length === 1
                                    ? 'Download the declaration (PDF)'
                                    : `Download all ${clients.length} (PDF)`
                            }
                            detail="A page per client, ready to print and notarize."
                        />
                    )}
                </div>
            </div>
        </>
    );
}

function Fill({ children }: { children: React.ReactNode }) {
    return (
        <span className="rounded bg-highlight px-1 font-mono text-[14px] font-bold">
            {children}
        </span>
    );
}
