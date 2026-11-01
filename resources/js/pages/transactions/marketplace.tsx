import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, FileSpreadsheet } from 'lucide-react';
import { useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, minusPeso, peso, plural } from '@/lib/format';
import { importMethod as importStatement } from '@/routes/transactions';
import {
    cancel,
    columns as saveColumns,
    confirm,
    upload,
} from '@/routes/transactions/marketplace';

type Mapping = {
    date: number | null;
    order: number | null;
    sales: number | null;
    fees: number | null;
    withheld: number | null;
    payout: number | null;
};

type Summary = {
    orders: number;
    payouts: number;
    sales: number;
    fees: number;
    withheld: number;
    paid_out: number;
    to_import: number;
    duplicates: number;
    filed: number;
    invalid: number;
    import_sales: number;
    import_fees: number;
    import_withheld: number;
};

type Props = {
    report: {
        name: string;
        platform: string;
        columns: string[];
        mapping: Mapping;
        matched: boolean;
        from: string | null;
        to: string | null;
    } | null;
    summary?: Summary | null;
    rate?: number;
    exemptUpTo?: number;
    platforms: string[];
};

export default function MarketplaceImport({
    report,
    summary = null,
    rate = 0,
    exemptUpTo = 0,
    platforms,
}: Props) {
    const [changingColumns, setChangingColumns] = useState(false);
    const [importing, setImporting] = useState(false);

    return (
        <>
            <Head title="Import marketplace payouts" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={importStatement().url}
                    aria-label="Back to import"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Import marketplace payouts
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {report
                            ? `${report.platform} payout report (CSV)${report.from && report.to ? ` · ${formatDate(report.from)} to ${formatDate(report.to)}` : ''}`
                            : 'Online marketplace payout report (CSV)'}
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    {report === null ? (
                        <UploadCard platforms={platforms} />
                    ) : (
                        <>
                            <SectionCard className="flex items-center gap-4 p-4">
                                <span className="flex size-[70px] shrink-0 items-center justify-center rounded-xl bg-[#E3E7EC] text-slate-600">
                                    <FileSpreadsheet
                                        className="size-6"
                                        aria-hidden
                                    />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-[15px] font-bold">
                                        {report.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {summary
                                            ? `${plural(summary.orders, 'order')} read${summary.payouts > 0 ? ` · ${plural(summary.payouts, 'payout')}` : ''}`
                                            : 'Choose the columns to read it'}
                                    </p>
                                </div>
                                {report.matched && !changingColumns && (
                                    <Button
                                        variant="ghost"
                                        onClick={() => setChangingColumns(true)}
                                    >
                                        Change columns
                                    </Button>
                                )}
                            </SectionCard>

                            {(!report.matched || changingColumns) && (
                                <ColumnsCard
                                    columns={report.columns}
                                    mapping={report.mapping}
                                    onDone={() => setChangingColumns(false)}
                                />
                            )}

                            {summary && (
                                <>
                                    <SectionCard title="What we found">
                                        <dl className="text-[15px] tabular-nums">
                                            <Row
                                                label="Gross sales (what buyers paid)"
                                                value={peso(summary.sales)}
                                            />
                                            <Row
                                                label="Platform fees and commissions"
                                                value={minusPeso(summary.fees)}
                                            />
                                            <Row
                                                label={`Withheld by the platform (${Math.round(rate * 1000) / 10}%)`}
                                                value={minusPeso(
                                                    summary.withheld,
                                                )}
                                            />
                                            <div className="flex items-center justify-between pt-3">
                                                <dt className="font-bold">
                                                    Paid out to you
                                                </dt>
                                                <dd className="text-xl font-extrabold">
                                                    {peso(summary.paid_out)}
                                                </dd>
                                            </div>
                                        </dl>
                                    </SectionCard>

                                    <SectionCard title="How we record it">
                                        <dl className="text-[15px] tabular-nums">
                                            <Row
                                                label="Sales"
                                                value={peso(
                                                    summary.import_sales,
                                                )}
                                            />
                                            <Row
                                                label="Expense: platform fees"
                                                value={peso(
                                                    summary.import_fees,
                                                )}
                                            />
                                            <Row
                                                label={`Tax credit (needs ${report.platform}'s 2307)`}
                                                value={peso(
                                                    summary.import_withheld,
                                                )}
                                            />
                                            <Row
                                                label="Duplicates skipped"
                                                value={String(
                                                    summary.duplicates,
                                                )}
                                            />
                                            {summary.filed > 0 && (
                                                <Row
                                                    label="In a filed quarter, left out"
                                                    value={String(
                                                        summary.filed,
                                                    )}
                                                />
                                            )}
                                            {summary.invalid > 0 && (
                                                <Row
                                                    label="Lines we couldn't read"
                                                    value={String(
                                                        summary.invalid,
                                                    )}
                                                />
                                            )}
                                        </dl>
                                    </SectionCard>
                                </>
                            )}
                        </>
                    )}
                </div>

                <div className="flex flex-col gap-4">
                    <Note title="Sales are what buyers paid">
                        Your income is the full sale, not the payout. Fees are
                        recorded as an expense, and the withholding as a credit.
                    </Note>
                    <Note
                        tone="success"
                        title={`Under ${peso(exemptUpTo || 500_000)} a year?`}
                    >
                        If your yearly remittances stay at{' '}
                        {peso(exemptUpTo || 500_000)} or less, the platform
                        shouldn't withhold, but you must give it a sworn
                        declaration received by the BIR, before Jan 20.
                    </Note>
                    {summary && summary.import_withheld > 0 && report && (
                        <Note tone="warning" title="Ask for the 2307">
                            The {peso(summary.import_withheld)} counts only with{' '}
                            {report.platform}'s 2307. We'll track it like a
                            client's.
                        </Note>
                    )}

                    {report && (
                        <>
                            <Button
                                variant="outline"
                                className="h-12 rounded-xl text-[15px] font-bold"
                                onClick={() => router.delete(cancel().url)}
                            >
                                Cancel
                            </Button>
                            <Button
                                disabled={
                                    !summary ||
                                    summary.to_import === 0 ||
                                    importing
                                }
                                className="h-12 rounded-xl text-[15px] font-bold"
                                onClick={() =>
                                    router.post(
                                        confirm().url,
                                        {},
                                        {
                                            onStart: () => setImporting(true),
                                            onFinish: () => setImporting(false),
                                        },
                                    )
                                }
                            >
                                {summary && summary.to_import > 0
                                    ? `Import ${plural(summary.to_import, 'order')}`
                                    : 'Nothing new to import'}
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-3 border-b border-divider py-3 last:border-0">
            <dt>{label}</dt>
            <dd className="font-bold">{value}</dd>
        </div>
    );
}

function UploadCard({ platforms }: { platforms: string[] }) {
    const [other, setOther] = useState(false);
    const form = useForm<{ platform: string; report: File | null }>({
        platform: platforms[0] ?? '',
        report: null,
    });

    return (
        <SectionCard title="Choose a payout report">
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(upload());
                }}
                className="flex max-w-lg flex-col gap-4"
            >
                <p className="text-[15px] text-muted-foreground">
                    Download the payout or income report as a CSV from your
                    seller center. We read each order's sale, fees and
                    withholding, and skip orders you already imported. Nothing
                    is uploaded anywhere.
                </p>
                <div className="flex flex-col gap-1.5">
                    <span className="text-sm font-bold">Marketplace</span>
                    <ChoiceChips
                        label="Marketplace"
                        value={other ? 'other' : form.data.platform}
                        onChange={(value) => {
                            setOther(value === 'other');
                            form.setData(
                                'platform',
                                value === 'other' ? '' : value,
                            );
                        }}
                        options={[
                            ...platforms.map((p) => ({ value: p, label: p })),
                            { value: 'other', label: 'Other' },
                        ]}
                    />
                    {other && (
                        <Input
                            aria-label="Marketplace name"
                            placeholder="Marketplace name"
                            value={form.data.platform}
                            onChange={(event) =>
                                form.setData('platform', event.target.value)
                            }
                            className="h-11 rounded-xl"
                        />
                    )}
                    <FieldError message={form.errors.platform} />
                </div>
                <Input
                    type="file"
                    accept=".csv,text/csv,.txt"
                    aria-label="Payout report CSV"
                    onChange={(event) =>
                        form.setData('report', event.target.files?.[0] ?? null)
                    }
                    className="h-11 rounded-xl bg-card py-2"
                />
                <FieldError message={form.errors.report} />
                <Button
                    type="submit"
                    disabled={
                        !form.data.report ||
                        form.data.platform.trim() === '' ||
                        form.processing
                    }
                    className="h-11 self-start rounded-xl px-5 font-bold"
                >
                    {form.processing ? 'Reading…' : 'Read report'}
                </Button>
            </form>
        </SectionCard>
    );
}

function ColumnsCard({
    columns,
    mapping,
    onDone,
}: {
    columns: string[];
    mapping: Mapping;
    onDone: () => void;
}) {
    const value = (at: number | null) => (at === null ? '' : String(at));
    const form = useForm({
        date: value(mapping.date),
        order: value(mapping.order),
        sales: value(mapping.sales),
        fees: value(mapping.fees),
        withheld: value(mapping.withheld),
        payout: value(mapping.payout),
    });
    const fields: [keyof typeof form.data, string][] = [
        ['date', 'Order date'],
        ['sales', 'What the buyer paid'],
        ['order', 'Order number (optional)'],
        ['fees', 'Fees (optional)'],
        ['withheld', 'Tax withheld (optional)'],
        ['payout', 'Payout ID (optional)'],
    ];

    return (
        <SectionCard title="Which columns are which?">
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(saveColumns(), { onSuccess: onDone });
                }}
                className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
            >
                {fields.map(([field, label]) => (
                    <div key={field} className="flex flex-col gap-1.5">
                        <Label htmlFor={`column-${field}`}>{label}</Label>
                        <select
                            id={`column-${field}`}
                            value={form.data[field]}
                            onChange={(event) =>
                                form.setData(field, event.target.value)
                            }
                            className="h-11 rounded-xl border border-input bg-card px-3 text-sm"
                        >
                            <option value="">—</option>
                            {columns.map((column, i) => (
                                <option key={i} value={i}>
                                    {column || `Column ${i + 1}`}
                                </option>
                            ))}
                        </select>
                        <FieldError message={form.errors[field]} />
                    </div>
                ))}
                <div className="sm:col-span-2 lg:col-span-3">
                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-xl font-bold"
                    >
                        Use these columns
                    </Button>
                </div>
            </form>
        </SectionCard>
    );
}
