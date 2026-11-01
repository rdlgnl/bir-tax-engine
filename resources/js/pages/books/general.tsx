import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Fragment } from 'react';
import { MonthLink } from '@/components/books/month-link';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { formAmount, formatDate } from '@/lib/format';
import { general, looseLeaf, show } from '@/routes/books';
import { pdf as generalPdf } from '@/routes/books/general';
import type { BooksPeriod, BooksType, JournalEntry, Ledger } from '@/types';

type Props = {
    period: BooksPeriod;
    previous: BooksPeriod | null;
    next: BooksPeriod | null;
    booksType: BooksType;
    accounts: Record<number, string>;
    journal: JournalEntry[];
    ledger: Ledger;
};

const amount = (value: number) => (value > 0 ? formAmount(value) : '');

export default function BooksGeneral({
    period,
    previous,
    next,
    booksType,
    accounts,
    journal,
    ledger,
}: Props) {
    const month = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(period.year, period.month - 1, 1),
    );
    const args = [period.year, period.month] as [number, number];
    const monthHref = (to: BooksPeriod) => general([to.year, to.month]).url;

    return (
        <>
            <Head
                title={`General journal and ledger · ${month} ${period.year}`}
            />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={show(args).url}
                    aria-label="Back to the cash journals"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div className="flex-1">
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        General journal and ledger
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {month} {period.year} · every entry, posted to its
                        account
                    </p>
                </div>
                <nav aria-label="Month" className="flex gap-1.5">
                    <MonthLink
                        to={previous}
                        href={monthHref}
                        label="Previous month"
                    >
                        <ChevronLeft className="size-4" aria-hidden />
                    </MonthLink>
                    <MonthLink to={next} href={monthHref} label="Next month">
                        <ChevronRight className="size-4" aria-hidden />
                    </MonthLink>
                </nav>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex min-w-0 flex-col gap-5">
                    <SectionCard title="General journal">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[560px] text-sm tabular-nums">
                                <thead>
                                    <tr className="border-b border-divider text-left text-xs font-bold text-slate-600">
                                        <th className="py-2 pr-3">Date</th>
                                        <th className="py-2 pr-3">
                                            Account and explanation
                                        </th>
                                        <th className="py-2 pr-3 text-right">
                                            Debit
                                        </th>
                                        <th className="py-2 text-right">
                                            Credit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {journal.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="py-3 text-muted-foreground"
                                            >
                                                No entries this month.
                                            </td>
                                        </tr>
                                    )}
                                    {journal.map((entry) => (
                                        <Fragment key={entry.ref}>
                                            {entry.lines.map((line, i) => (
                                                <tr
                                                    key={`${entry.ref}-${i}`}
                                                    className={
                                                        i === 0
                                                            ? 'border-t border-divider'
                                                            : undefined
                                                    }
                                                >
                                                    <td className="py-1.5 pr-3 align-top whitespace-nowrap">
                                                        {i === 0 && (
                                                            <>
                                                                {formatDate(
                                                                    entry.date,
                                                                )}
                                                                <span className="block text-xs text-muted-foreground">
                                                                    {entry.ref}
                                                                </span>
                                                            </>
                                                        )}
                                                    </td>
                                                    <td
                                                        className={
                                                            line.credit > 0
                                                                ? 'py-1.5 pr-3 pl-6'
                                                                : 'py-1.5 pr-3'
                                                        }
                                                    >
                                                        {line.account}{' '}
                                                        {accounts[line.account]}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-right">
                                                        {amount(line.debit)}
                                                    </td>
                                                    <td className="py-1.5 text-right">
                                                        {amount(line.credit)}
                                                    </td>
                                                </tr>
                                            ))}
                                            <tr>
                                                <td />
                                                <td
                                                    colSpan={3}
                                                    className="pb-2.5 text-[13px] text-muted-foreground"
                                                >
                                                    {entry.description}
                                                </td>
                                            </tr>
                                        </Fragment>
                                    ))}
                                    <tr className="border-t-2 border-ink font-bold">
                                        <td className="py-2" colSpan={2}>
                                            Total
                                        </td>
                                        <td className="py-2 pr-3 text-right">
                                            {formAmount(ledger.debits)}
                                        </td>
                                        <td className="py-2 text-right">
                                            {formAmount(ledger.credits)}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </SectionCard>

                    <SectionCard title="General ledger">
                        {ledger.accounts.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No accounts with entries yet this year.
                            </p>
                        ) : (
                            <div className="flex flex-col gap-6">
                                {ledger.accounts.map((account) => (
                                    <div
                                        key={account.code}
                                        className="overflow-x-auto"
                                    >
                                        <h3 className="mb-1 text-[15px] font-extrabold">
                                            {account.code} {account.name}
                                        </h3>
                                        <table className="w-full min-w-[560px] text-sm tabular-nums">
                                            <thead>
                                                <tr className="border-b border-divider text-left text-xs font-bold text-slate-600">
                                                    <th className="py-1.5 pr-3">
                                                        Date
                                                    </th>
                                                    <th className="py-1.5 pr-3">
                                                        Explanation
                                                    </th>
                                                    <th className="py-1.5 pr-3 text-right">
                                                        Debit
                                                    </th>
                                                    <th className="py-1.5 pr-3 text-right">
                                                        Credit
                                                    </th>
                                                    <th className="py-1.5 text-right">
                                                        Balance
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-divider">
                                                <tr className="text-muted-foreground">
                                                    <td className="py-1.5 pr-3">
                                                        {formatDate(
                                                            `${period.year}-${String(period.month).padStart(2, '0')}-01`,
                                                        )}
                                                    </td>
                                                    <td
                                                        className="py-1.5 pr-3"
                                                        colSpan={3}
                                                    >
                                                        Balance forward
                                                    </td>
                                                    <td className="py-1.5 text-right">
                                                        {formAmount(
                                                            account.opening,
                                                        )}
                                                    </td>
                                                </tr>
                                                {account.postings.map(
                                                    (posting, i) => (
                                                        <tr key={i}>
                                                            <td className="py-1.5 pr-3 whitespace-nowrap">
                                                                {formatDate(
                                                                    posting.date,
                                                                )}
                                                            </td>
                                                            <td className="py-1.5 pr-3">
                                                                {
                                                                    posting.description
                                                                }
                                                                <span className="block text-xs text-muted-foreground">
                                                                    {
                                                                        posting.ref
                                                                    }
                                                                </span>
                                                            </td>
                                                            <td className="py-1.5 pr-3 text-right">
                                                                {amount(
                                                                    posting.debit,
                                                                )}
                                                            </td>
                                                            <td className="py-1.5 pr-3 text-right">
                                                                {amount(
                                                                    posting.credit,
                                                                )}
                                                            </td>
                                                            <td className="py-1.5 text-right">
                                                                {formAmount(
                                                                    posting.balance,
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ),
                                                )}
                                                <tr className="font-bold">
                                                    <td
                                                        className="py-1.5 pr-3"
                                                        colSpan={2}
                                                    >
                                                        {month}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-right">
                                                        {formAmount(
                                                            account.debits,
                                                        )}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-right">
                                                        {formAmount(
                                                            account.credits,
                                                        )}
                                                    </td>
                                                    <td className="py-1.5 text-right">
                                                        {formAmount(
                                                            account.closing,
                                                        )}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                ))}
                            </div>
                        )}
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <DownloadCard
                        href={generalPdf(args).url}
                        title={`Download ${month} (PDF)`}
                        detail="The general journal and ledger, page-numbered, ready to print."
                    />
                    <Note title="On a cash basis">
                        Like the cash journals: money that came in or went out,
                        taxes paid with returns you marked paid, and the tax
                        withheld from salaries. An unpaid invoice waits until
                        it's paid.
                    </Note>
                    <Note
                        tone="warning"
                        title="Balances start with your first records"
                    >
                        They carry from year to year, the year's income and
                        expenses closing into owner's capital. Money you had
                        before your first record in the app isn't known: add it
                        by hand, or ask your bookkeeper.
                    </Note>
                    {booksType === 'loose_leaf' ? (
                        <Note title="Bind them with the cash journals">
                            All four books, printed monthly, are bound together
                            after the year ends.{' '}
                            <Link
                                href={looseLeaf().url}
                                className="font-bold underline"
                            >
                                Loose-leaf steps
                            </Link>
                        </Note>
                    ) : (
                        <Note title="Registered a journal and ledger?">
                            Copy these entries into your BIR-stamped general
                            journal and ledger.
                        </Note>
                    )}
                </div>
            </div>
        </>
    );
}
