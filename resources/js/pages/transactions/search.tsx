import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, peso, plural } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    exportMethod,
    index,
    search as searchRoute,
} from '@/routes/transactions';
import type { Transaction } from '@/types';

type Period = 'quarter' | 'year' | 'custom';
type Flag = 'missing_2307' | 'no_invoice' | 'after_filing';
type TypeFilter = 'payment' | 'invoice' | 'expense' | 'withheld';

type Filters = {
    search: string;
    type: TypeFilter | null;
    period: Period;
    year: number;
    from: string;
    to: string;
    flag: Flag | null;
};

type Props = {
    filters: Filters;
    results: Transaction[];
    summary: { count: number; billed: number; spent: number };
    flagCounts: Record<Flag, number>;
    years: number[];
};

const flagLabels: Record<Flag, string> = {
    missing_2307: 'Missing a 2307',
    no_invoice: 'Not linked to an invoice',
    after_filing: 'Added after the quarter was filed',
};

/** "₱1,500 withheld · 2307 due in January" */
function detail(t: Transaction): string {
    const parts: string[] = [];

    if (t.type === 'invoice') {
        parts.push(
            `Invoice ${t.invoice_number} · ${t.invoice_paid ? 'paid' : 'unpaid'}`,
        );
    } else if (t.type === 'expense') {
        parts.push(`Expense · ${t.category_label ?? 'other'}`);
    } else if (t.invoice_number) {
        parts.push(`Invoice ${t.invoice_number}`);
    }

    if (t.tax_withheld > 0) {
        parts.push(`${peso(t.tax_withheld)} withheld`);
        parts.push(
            {
                received: '2307 received',
                pending: '2307 not in yet',
                missing: '2307 missing',
                not_needed: '',
            }[t.certificate_status],
        );
    }

    return parts.filter(Boolean).join(' · ') || 'Payment received';
}

export default function Search({
    filters,
    results,
    summary,
    flagCounts,
    years,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const firstRender = useRef(true);
    const query = (next: Partial<Filters> = {}) => {
        const f = { ...filters, search, ...next };

        return {
            search: f.search || undefined,
            type: f.type ?? undefined,
            period: f.period,
            year: f.period === 'year' ? f.year : undefined,
            from: f.period === 'custom' ? f.from : undefined,
            to: f.period === 'custom' ? f.to : undefined,
            flag: f.flag ?? undefined,
        };
    };
    const apply = (next: Partial<Filters>) =>
        router.get(searchRoute().url, query(next), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = setTimeout(() => apply({ search }), 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const filtered =
        filters.search !== '' ||
        filters.type !== null ||
        filters.flag !== null ||
        filters.period !== 'year';

    return (
        <>
            <Head title="Find a record" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={index().url}
                    aria-label="Back to Transactions"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Find a record
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Search and filter all your records
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="search">Search</Label>
                        <Input
                            id="search"
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="h-11 rounded-xl bg-card"
                            autoFocus
                        />
                        <p className="text-[13px] text-muted-foreground">
                            Client, invoice number, amount or note
                        </p>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <span className="text-sm font-bold">Type</span>
                        <ChoiceChips<string>
                            label="Type"
                            variant="segmented"
                            value={filters.type ?? 'all'}
                            onChange={(value) =>
                                apply({
                                    type:
                                        value === 'all'
                                            ? null
                                            : (value as TypeFilter),
                                })
                            }
                            options={[
                                { value: 'all', label: 'All' },
                                { value: 'payment', label: 'Received' },
                                { value: 'invoice', label: 'Invoices' },
                                { value: 'expense', label: 'Expenses' },
                                { value: 'withheld', label: '2307s' },
                            ]}
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <span className="text-sm font-bold">Period</span>
                        <ChoiceChips<string>
                            label="Period"
                            variant="segmented"
                            value={
                                filters.period === 'year'
                                    ? String(filters.year)
                                    : filters.period
                            }
                            onChange={(value) =>
                                value === 'quarter' || value === 'custom'
                                    ? apply({ period: value })
                                    : apply({
                                          period: 'year',
                                          year: Number(value),
                                      })
                            }
                            options={[
                                { value: 'quarter', label: 'This quarter' },
                                ...years.map((year) => ({
                                    value: String(year),
                                    label: String(year),
                                })),
                                { value: 'custom', label: 'Custom' },
                            ]}
                        />
                        {filters.period === 'custom' && (
                            <div className="mt-1 grid max-w-md grid-cols-2 gap-2">
                                <Input
                                    type="date"
                                    aria-label="From"
                                    value={filters.from}
                                    onChange={(event) =>
                                        apply({ from: event.target.value })
                                    }
                                    className="h-11 rounded-xl bg-card"
                                />
                                <Input
                                    type="date"
                                    aria-label="To"
                                    value={filters.to}
                                    min={filters.from}
                                    onChange={(event) =>
                                        apply({ to: event.target.value })
                                    }
                                    className="h-11 rounded-xl bg-card"
                                />
                            </div>
                        )}
                    </div>

                    <SectionCard
                        title={`${plural(summary.count, 'result')}${summary.billed > 0 ? ` · ${peso(summary.billed)} billed` : ''}${summary.spent > 0 ? ` · ${peso(summary.spent)} spent` : ''}`}
                    >
                        {results.length === 0 ? (
                            <p className="py-2 text-sm text-muted-foreground">
                                Nothing matches. Try fewer words or another
                                period.
                            </p>
                        ) : (
                            <ul className="divide-y divide-divider">
                                {results.map((t) => (
                                    <li
                                        key={t.id}
                                        className="flex items-center justify-between gap-3 py-3"
                                    >
                                        <span>
                                            <span className="block text-[15px] font-bold">
                                                {formatDate(t.occurred_on)} ·{' '}
                                                {t.counterparty}
                                            </span>
                                            <span className="text-[13px] text-muted-foreground">
                                                {detail(t)}
                                            </span>
                                        </span>
                                        <span className="font-bold tabular-nums">
                                            {t.type === 'expense' ? '−' : ''}
                                            {peso(t.amount)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <SectionCard title="Quick filters">
                        <ul className="divide-y divide-divider">
                            {(Object.keys(flagLabels) as Flag[]).map((flag) => (
                                <li key={flag}>
                                    <button
                                        type="button"
                                        aria-pressed={filters.flag === flag}
                                        onClick={() =>
                                            apply({
                                                flag:
                                                    filters.flag === flag
                                                        ? null
                                                        : flag,
                                            })
                                        }
                                        className={cn(
                                            'flex w-full items-center justify-between gap-3 py-3 text-left text-[15px]',
                                            filters.flag === flag &&
                                                'font-bold text-primary',
                                        )}
                                    >
                                        <span>{flagLabels[flag]}</span>
                                        <span className="shrink-0 font-bold">
                                            {plural(flagCounts[flag], 'record')}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </SectionCard>

                    <Note title="Exports follow your filters">
                        Download exactly what you see as a CSV that opens in
                        Excel, for you or your accountant.
                    </Note>

                    <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-1">
                        <Button
                            variant="outline"
                            disabled={!filtered}
                            className="h-12 rounded-xl text-[15px] font-bold"
                            onClick={() => {
                                setSearch('');
                                router.get(
                                    searchRoute().url,
                                    {},
                                    { replace: true },
                                );
                            }}
                        >
                            Clear filters
                        </Button>
                        {results.length > 0 ? (
                            <Button
                                asChild
                                className="h-12 rounded-xl text-[15px] font-bold"
                            >
                                <a
                                    href={exportMethod({ query: query() }).url}
                                    download
                                >
                                    Export{' '}
                                    {results.length === 1
                                        ? 'this one'
                                        : `these ${results.length}`}
                                </a>
                            </Button>
                        ) : (
                            <Button
                                disabled
                                className="h-12 rounded-xl text-[15px] font-bold"
                            >
                                Export
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
