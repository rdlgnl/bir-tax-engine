import { Head, Link, router } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import { history } from '@/routes';
import { copied, looseLeaf, pdf, show, type } from '@/routes/books';
import type {
    BooksPeriod,
    BooksType,
    CashDisbursement,
    CashReceipt,
    JournalTotals,
} from '@/types';

type Props = {
    period: BooksPeriod;
    previous: BooksPeriod | null;
    next: BooksPeriod | null;
    booksType: BooksType;
    regime: string | null;
    copiedAt: string | null;
    retentionYears: number;
    computerizedLimit: number;
    receipts: CashReceipt[];
    disbursements: CashDisbursement[];
    totals: JournalTotals;
};

function monthName(period: BooksPeriod): string {
    return new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(period.year, period.month - 1, 1),
    );
}

export default function BooksShow({
    period,
    previous,
    next,
    booksType,
    regime,
    copiedAt,
    retentionYears,
    computerizedLimit,
    receipts,
    disbursements,
    totals,
}: Props) {
    const month = monthName(period);
    const args = [period.year, period.month] as [number, number];

    return (
        <>
            <Head title={`Books of accounts · ${month} ${period.year}`} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={history().url}
                    aria-label="Back to History & reports"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div className="flex-1">
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Books of accounts
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {month} {period.year} · cash receipts and disbursements
                    </p>
                </div>
                <nav aria-label="Month" className="flex gap-1.5">
                    <MonthLink to={previous} label="Previous month">
                        <ChevronLeft className="size-4" aria-hidden />
                    </MonthLink>
                    <MonthLink to={next} label="Next month">
                        <ChevronRight className="size-4" aria-hidden />
                    </MonthLink>
                </nav>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <div className="flex flex-col gap-2">
                        <span className="text-[15px] font-bold">
                            How your books are registered
                        </span>
                        <ChoiceChips<BooksType>
                            label="How your books are registered"
                            variant="segmented"
                            value={booksType}
                            onChange={(value) =>
                                router.put(
                                    type().url,
                                    { books_type: value },
                                    { preserveScroll: true },
                                )
                            }
                            options={[
                                { value: 'manual', label: 'Manual (stamped)' },
                                { value: 'loose_leaf', label: 'Loose-leaf' },
                            ]}
                            className="max-w-md"
                        />
                        <p className="text-[13px] text-muted-foreground">
                            {booksType === 'manual'
                                ? 'Copy these entries into your BIR-stamped journals.'
                                : 'Print each month, bind them at year end, and keep the Permit to Use.'}
                        </p>
                    </div>

                    <SectionCard title="Cash receipts journal">
                        <Journal
                            columns={[
                                'Date',
                                'Received from',
                                'Invoice',
                                'Withheld',
                                'Received',
                            ]}
                            empty="No money received this month."
                            rows={receipts.map((r) => [
                                formatDate(r.occurred_on),
                                r.counterparty,
                                r.invoice_number ?? '—',
                                peso(r.withheld),
                                <strong key="received">
                                    {peso(r.received)}
                                </strong>,
                            ])}
                            total={[
                                peso(totals.withheld),
                                peso(totals.received),
                            ]}
                        />
                    </SectionCard>

                    <SectionCard title="Cash disbursements journal">
                        <Journal
                            columns={[
                                'Date',
                                'Paid to',
                                'Receipt',
                                'For',
                                'Amount',
                            ]}
                            empty="No expenses recorded this month."
                            rows={disbursements.map((d) => [
                                formatDate(d.occurred_on),
                                d.counterparty,
                                d.receipt_number ?? '—',
                                d.category ?? '—',
                                <strong key="amount">{peso(d.amount)}</strong>,
                            ])}
                            total={[peso(totals.disbursed)]}
                        />
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <DownloadCard
                        href={pdf(args).url}
                        title={`Download ${month} books (PDF)`}
                        detail="Both journals, page-numbered, ready to copy or print."
                    />

                    <Note title={`Keep them ${retentionYears} years`}>
                        Books and records must be kept for {retentionYears}{' '}
                        years after the return they support is filed (EOPT).
                    </Note>
                    <Note
                        tone="warning"
                        title={`Above ${peso(computerizedLimit)} in sales`}
                    >
                        Computerized books then need a separate BIR permit.
                        We'll tell you before you get there.
                    </Note>
                    {booksType === 'manual' && (
                        <Note title="Prefer printing to copying?">
                            Register loose-leaf books and print these journals
                            instead.{' '}
                            <Link
                                href={looseLeaf().url}
                                className="font-bold underline"
                            >
                                How
                            </Link>
                        </Note>
                    )}
                    {regime === 'eight_percent' && (
                        <Note title="On 8%, expenses don't lower your tax">
                            Record them anyway: the books must be complete, and
                            they matter if you switch to graduated rates.
                        </Note>
                    )}

                    <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-1">
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <Link href={history().url}>Back</Link>
                        </Button>
                        {copiedAt ? (
                            <p className="flex h-12 items-center justify-center gap-2 rounded-xl bg-success-soft text-[15px] font-bold text-success">
                                <Check className="size-4" aria-hidden />
                                Copied {formatDate(copiedAt)}
                            </p>
                        ) : (
                            <Button
                                className="h-12 rounded-xl text-[15px] font-bold"
                                onClick={() =>
                                    router.post(
                                        copied(args).url,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Mark {month} copied
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function MonthLink({
    to,
    label,
    children,
}: {
    to: BooksPeriod | null;
    label: string;
    children: ReactNode;
}) {
    const className =
        'flex size-10 items-center justify-center rounded-xl border bg-card';

    if (!to) {
        return (
            <span
                aria-disabled="true"
                aria-label={label}
                className={`${className} text-slate-300`}
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={show([to.year, to.month]).url}
            aria-label={label}
            className={`${className} hover:bg-muted`}
        >
            {children}
        </Link>
    );
}

/** One journal: the last column(s) are amounts, totalled underneath. */
function Journal({
    columns,
    rows,
    total,
    empty,
}: {
    columns: string[];
    rows: ReactNode[][];
    total: ReactNode[];
    empty: string;
}) {
    const amountsFrom = columns.length - total.length;

    if (rows.length === 0) {
        return <p className="py-2 text-sm text-muted-foreground">{empty}</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm tabular-nums">
                <thead>
                    <tr className="border-b text-left text-xs font-bold text-slate-600">
                        {columns.map((column, i) => (
                            <th
                                key={column}
                                scope="col"
                                className={`px-1.5 py-2 ${i >= amountsFrom ? 'text-right' : ''}`}
                            >
                                {column}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((cells, r) => (
                        <tr key={r} className="border-b border-divider">
                            {cells.map((cell, i) => (
                                <td
                                    key={i}
                                    className={`px-1.5 py-3 ${i >= amountsFrom ? 'text-right' : ''}`}
                                >
                                    {cell}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
                {rows.length > 1 && (
                    <tfoot>
                        <tr className="font-bold">
                            <td colSpan={amountsFrom} className="px-1.5 py-3">
                                Total
                            </td>
                            {total.map((cell, i) => (
                                <td key={i} className="px-1.5 py-3 text-right">
                                    {cell}
                                </td>
                            ))}
                        </tr>
                    </tfoot>
                )}
            </table>
        </div>
    );
}
