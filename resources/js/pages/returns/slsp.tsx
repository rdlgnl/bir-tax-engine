import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, peso, plural, quarterMonths } from '@/lib/format';
import { profile } from '@/routes';
import { show as prepare } from '@/routes/returns';
import { exportMethod } from '@/routes/returns/slsp';

type Sale = {
    name: string;
    tin: string | null;
    invoices: number;
    vatable: number;
    zero_rated: number;
    exempt: number;
    output_vat: number;
};
type Purchase = {
    name: string;
    tin: string | null;
    invoices: number;
    amount: number;
    input_vat: number;
};

type Props = {
    year: number;
    quarter: number;
    sales: Sale[];
    purchases: Purchase[];
    excluded: { name: string; date: string; amount: number; vat: number }[];
    totals: {
        vatable: number;
        output_vat: number;
        input_vat: number;
        excluded: number;
    };
    dueDate: string;
    files: { sales: string | null; purchases: string | null };
};

export default function Slsp({
    year,
    quarter,
    sales,
    purchases,
    excluded,
    totals,
    dueDate,
    files,
}: Props) {
    const back = prepare([year, quarter, '2550Q']).url;
    const missingTins = [...sales, ...purchases].filter(
        (row) => !row.tin,
    ).length;

    return (
        <>
            <Head title={`SLSP · Q${quarter} ${year}`} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={back}
                    aria-label="Back to the 2550Q"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Summary list of sales and purchases
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        SLSP · Q{quarter} {year} ({quarterMonths(quarter)}) ·
                        for your VAT return
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <SectionCard title="Sales (by customer)">
                        <List
                            empty="No sales this quarter: the list is filed as nil."
                            head={[
                                'Customer',
                                'TIN',
                                'Invoices',
                                'Vatable sales',
                            ]}
                            rows={sales.map((row) => [
                                row.name,
                                row.tin,
                                row.invoices,
                                peso(row.vatable),
                            ])}
                        />
                    </SectionCard>
                    <SectionCard title="Purchases (by supplier)">
                        <List
                            empty="No purchases with VAT you can claim this quarter."
                            head={['Supplier', 'TIN', 'Invoices', 'Input VAT']}
                            rows={purchases.map((row) => [
                                row.name,
                                row.tin,
                                row.invoices,
                                peso(row.input_vat),
                            ])}
                        />
                    </SectionCard>
                    <SectionCard title="Totals">
                        <dl className="divide-y divide-divider text-[15px] tabular-nums">
                            <Row
                                label="Vatable sales"
                                value={peso(totals.vatable)}
                            />
                            <Row
                                label="Output VAT"
                                value={peso(totals.output_vat)}
                            />
                            <Row
                                label="Input VAT claimed"
                                value={peso(totals.input_vat)}
                            />
                            <Row
                                label="Excluded invoices"
                                value={
                                    totals.excluded === 0
                                        ? 'None'
                                        : `${totals.excluded} · not in your name`
                                }
                            />
                        </dl>
                        {excluded.length > 0 && (
                            <ul className="mt-2 text-[13px] text-muted-foreground">
                                {excluded.map((row) => (
                                    <li key={`${row.name}-${row.date}`}>
                                        {formatDate(row.date)} · {row.name} ·{' '}
                                        {peso(row.vat)} VAT
                                    </li>
                                ))}
                            </ul>
                        )}
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <Note
                        title={`Due ${formatDate(dueDate, 'short')}, ${dueDate.slice(0, 4)}`}
                    >
                        Same day as the 2550Q. Submitted electronically; keep
                        the BIR's acknowledgment.
                    </Note>
                    <Note title="Needed to claim input VAT">
                        The purchases list backs the {peso(totals.input_vat)}{' '}
                        you deduct. A quarter with no sales or purchases still
                        needs a "nil" list.
                    </Note>
                    {missingTins > 0 && (
                        <Note
                            tone="warning"
                            title={`${plural(missingTins, 'TIN')} missing`}
                        >
                            Add each customer's and supplier's TIN on their
                            records; the BIR's lists are matched by TIN.
                        </Note>
                    )}
                    {files.sales && files.purchases ? (
                        <>
                            <DownloadCard
                                href={
                                    exportMethod([year, quarter, 'sales']).url
                                }
                                title="Sales list (.DAT)"
                                detail={`${files.sales} · in the BIR's RELIEF format`}
                            />
                            <DownloadCard
                                href={
                                    exportMethod([year, quarter, 'purchases'])
                                        .url
                                }
                                title="Purchases list (.DAT)"
                                detail={`${files.purchases} · in the BIR's RELIEF format`}
                            />
                        </>
                    ) : (
                        <Note tone="warning" title="Your TIN is missing">
                            The files are named by your TIN.{' '}
                            <Link
                                href={profile().url}
                                className="font-bold underline"
                            >
                                Add it to your profile
                            </Link>
                        </Note>
                    )}
                    <a
                        href={exportMethod([year, quarter, 'csv']).url}
                        download
                        className="rounded-card border bg-card p-4 text-[15px] font-bold hover:bg-muted"
                    >
                        Download both lists as CSV
                        <span className="block text-sm font-normal text-muted-foreground">
                            Opens in Excel, to check before filing
                        </span>
                    </a>
                    <Button
                        asChild
                        variant="outline"
                        className="h-12 rounded-xl text-[15px] font-bold"
                    >
                        <Link href={back}>Back to the 2550Q</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

function List({
    head,
    rows,
    empty,
}: {
    head: string[];
    rows: (string | number | null)[][];
    empty: string;
}) {
    if (rows.length === 0) {
        return <p className="py-2 text-sm text-muted-foreground">{empty}</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm tabular-nums">
                <thead>
                    <tr className="border-b text-left text-xs font-bold text-slate-600">
                        {head.map((cell, i) => (
                            <th
                                key={cell}
                                scope="col"
                                className={`px-1.5 py-2 ${i >= 2 ? 'text-right' : ''}`}
                            >
                                {cell}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr
                            key={String(row[0])}
                            className="border-b border-divider"
                        >
                            {row.map((cell, i) => (
                                <td
                                    key={i}
                                    className={`px-1.5 py-2.5 ${i >= 2 ? 'text-right' : ''} ${i === 3 ? 'font-bold' : ''}`}
                                >
                                    {cell ?? (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function Row({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex justify-between gap-3 py-2.5">
            <dt>{label}</dt>
            <dd className="text-right font-bold">{value}</dd>
        </div>
    );
}
