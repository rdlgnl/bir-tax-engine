import { Head, Link, router, usePage } from '@inertiajs/react';
import { Lock, Plus, Search, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { SectionCard } from '@/components/section-card';
import { AddTransactionPanel } from '@/components/transactions/add-transaction-panel';
import { TransactionTable } from '@/components/transactions/transaction-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useMediaQuery } from '@/hooks/use-media-query';
import { peso } from '@/lib/format';
import {
    importMethod,
    index,
    search as findRecord,
} from '@/routes/transactions';
import type { TransactionWithEffect } from '@/types';

type Filters = {
    year: number;
    quarter: number | null;
    type: 'payment' | 'invoice' | 'expense' | 'withheld' | null;
    search: string;
};

type Props = {
    transactions: TransactionWithEffect[];
    filters: Filters;
    years: number[];
    yearGross: number;
    filedQuarters: number[];
    yearClosed: boolean;
};

const typeOptions = [
    { value: 'all', label: 'All types' },
    { value: 'payment', label: 'Payments' },
    { value: 'invoice', label: 'Invoices' },
    { value: 'expense', label: 'Expenses' },
    { value: 'withheld', label: 'With tax withheld' },
];

function filedLabel(quarters: number[]): string | null {
    const sorted = quarters.filter((q) => q < 4).sort((a, b) => a - b);

    if (sorted.length === 0) {
        return null;
    }

    return sorted.length === 1
        ? `Q${sorted[0]}`
        : `Q${sorted[0]}–Q${sorted[sorted.length - 1]}`;
}

export default function TransactionsIndex({
    transactions,
    filters,
    years,
    yearGross,
    filedQuarters,
    yearClosed,
}: Props) {
    const regime = usePage().props.transactionForm?.regime ?? null;
    const [search, setSearch] = useState(filters.search);
    const [editing, setEditing] = useState<TransactionWithEffect | null>(null);
    const [lockedNotice, setLockedNotice] = useState<number | null>(null);
    const [sheetOpen, setSheetOpen] = useState(false);
    const wide = useMediaQuery('(min-width: 1280px)');
    const firstRender = useRef(true);
    const filed = filedLabel(filedQuarters);

    function applyFilters(next: Partial<Filters>) {
        const merged = { ...filters, search, ...next };

        router.get(
            index().url,
            {
                year: merged.year,
                quarter: merged.quarter ?? undefined,
                type: merged.type ?? undefined,
                search: merged.search || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: [
                    'transactions',
                    'filters',
                    'yearGross',
                    'filedQuarters',
                    'yearClosed',
                ],
            },
        );
    }

    // Search as you type, a moment after the last keystroke.
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = setTimeout(() => applyFilters({ search }), 300);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function select(transaction: TransactionWithEffect) {
        if (yearClosed) {
            setEditing(null);
            setLockedNotice(transaction.quarter);

            return;
        }

        setLockedNotice(null);
        setEditing(transaction);
        setSheetOpen(!wide);
    }

    function closeEditor() {
        setEditing(null);
        setSheetOpen(false);
    }

    const lockedMessage = lockedNotice !== null && (
        <p
            role="status"
            className="mb-4 rounded-xl bg-warning-soft p-3 text-[13px] text-warning"
        >
            This year's annual return is filed, so its entries are locked.
            Changing them needs an amended annual return, which isn't available
            yet.
        </p>
    );

    return (
        <>
            <Head title="Transactions" />

            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Transactions
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {filters.year} · {peso(yearGross)} sales recorded
                        {filed && ` · ${filed} filed`}
                    </p>
                </div>
                <div className="flex gap-2.5">
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 rounded-xl px-5 text-[15px] font-bold"
                    >
                        <Link href={findRecord().url}>
                            <Search className="size-4" aria-hidden />
                            Find a record
                        </Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 rounded-xl px-5 text-[15px] font-bold"
                    >
                        <Link href={importMethod().url}>
                            <Upload className="size-4" aria-hidden />
                            Import statement
                        </Link>
                    </Button>
                    <Button
                        className="hidden h-11 rounded-xl px-5 text-[15px] font-bold lg:inline-flex xl:hidden"
                        onClick={() => {
                            setEditing(null);
                            setSheetOpen(true);
                        }}
                    >
                        <Plus className="size-5" aria-hidden />
                        Add transaction
                    </Button>
                </div>
            </header>

            {!wide && lockedMessage}

            <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_384px]">
                <SectionCard className="p-5">
                    <div className="mb-4 flex flex-wrap gap-2.5">
                        <div className="relative min-w-[220px] flex-1">
                            <Search
                                className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden
                            />
                            <Input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search client, amount, invoice no."
                                aria-label="Search transactions"
                                className="h-11 rounded-xl bg-card pl-10"
                            />
                        </div>
                        <Select
                            value={String(filters.year)}
                            onValueChange={(value) =>
                                applyFilters({ year: Number(value) })
                            }
                        >
                            <SelectTrigger
                                aria-label="Year"
                                className="!h-11 rounded-xl bg-card font-semibold"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {years.map((year) => (
                                    <SelectItem key={year} value={String(year)}>
                                        All of {year}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={filters.type ?? 'all'}
                            onValueChange={(value) =>
                                applyFilters({
                                    type:
                                        value === 'all'
                                            ? null
                                            : (value as Filters['type']),
                                })
                            }
                        >
                            <SelectTrigger
                                aria-label="Type"
                                className="!h-11 rounded-xl bg-card font-semibold"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {typeOptions.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {transactions.length === 0 ? (
                        <p className="py-10 text-center text-sm text-muted-foreground">
                            {filters.search || filters.type
                                ? 'Nothing matches. Try another search or filter.'
                                : `Nothing recorded for ${filters.year} yet.`}
                        </p>
                    ) : (
                        <TransactionTable
                            transactions={transactions}
                            regime={regime}
                            grouped
                            showCertificates
                            onSelect={select}
                            selectedId={editing?.id ?? null}
                        />
                    )}

                    {filed && (
                        <p className="mt-4 flex items-center gap-2 text-[13px] text-muted-foreground">
                            <Lock className="size-3.5" aria-hidden />
                            {filed} {filedQuarters.length === 1 ? 'is' : 'are'}{' '}
                            filed: changing an entry there means amending that
                            return, which we prepare for you.
                        </p>
                    )}
                </SectionCard>

                <aside
                    aria-label={editing ? 'Edit entry' : 'Add a transaction'}
                    className="hidden rounded-card border-2 border-primary bg-card p-5 xl:sticky xl:top-8 xl:block"
                >
                    {lockedMessage}
                    <AddTransactionPanel
                        key={editing?.id ?? 'new'}
                        editing={editing}
                        onDone={closeEditor}
                    />
                    {editing && (
                        <Button
                            variant="ghost"
                            className="mt-2 w-full"
                            onClick={closeEditor}
                        >
                            Cancel editing
                        </Button>
                    )}
                </aside>
            </div>

            {!wide && (
                <Sheet
                    open={sheetOpen}
                    onOpenChange={(open) =>
                        open ? setSheetOpen(true) : closeEditor()
                    }
                >
                    <SheetContent
                        side="right"
                        className="w-full overflow-y-auto p-5 sm:max-w-md"
                    >
                        <SheetTitle className="sr-only">
                            {editing ? 'Edit entry' : 'Add a transaction'}
                        </SheetTitle>
                        <SheetDescription className="sr-only">
                            Record money you received, an invoice, an expense or
                            a 2307.
                        </SheetDescription>
                        <AddTransactionPanel
                            key={editing?.id ?? 'new'}
                            editing={editing}
                            onDone={closeEditor}
                        />
                    </SheetContent>
                </Sheet>
            )}
        </>
    );
}
