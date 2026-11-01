import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, FileText } from 'lucide-react';
import { useState } from 'react';
import { FieldError } from '@/components/field-error';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, peso, plural } from '@/lib/format';
import { cn } from '@/lib/utils';
import { payroll } from '@/routes';
import { create as lettersCreate } from '@/routes/letters';
import { create as loansCreate } from '@/routes/loans';
import { index, marketplace } from '@/routes/transactions';
import {
    cancel,
    columns as saveColumns,
    confirm,
    upload,
} from '@/routes/transactions/import';

type Status =
    | 'import'
    | 'ignore'
    | 'duplicate'
    | 'answer'
    | 'filed'
    | 'invalid';

type Row = {
    index: number;
    date: string | null;
    description: string;
    received: number;
    status: Status;
    client: string | null;
    amount: number;
    withheld: number;
    reason: string;
};

type Mapping = {
    date: number | null;
    description: number | null;
    amount: number | null;
    credit: number | null;
    debit: number | null;
};

type Props = {
    statement: {
        name: string;
        columns: string[];
        mapping: Mapping;
        matched: boolean;
        lines: number;
    } | null;
    summary?: {
        payments: number;
        total: number;
        clients: number;
        ignored: number;
        duplicates: number;
        answers: number;
        filed: number;
        invalid: number;
    };
    rows?: Row[];
};

type Answer = { kind: 'payment' | 'personal'; client: string };

const OTHER_THINGS: [string, string][] = [
    [
        'I refunded a client',
        'Lowers your sales for the quarter. For now, edit the original payment.',
    ],
    [
        'Other business income',
        'Selling old equipment, side commissions: record it as money received.',
    ],
    ['Bank interest', 'Already taxed 20% by the bank. Nothing to do.'],
    ['Gift or money from family', 'Not business income. Nothing to record.'],
];

const statusLabels: Record<Status, string> = {
    import: 'Import',
    answer: 'Waiting',
    duplicate: 'Already recorded',
    ignore: 'Ignore',
    filed: 'Quarter filed',
    invalid: 'Can’t read',
};

export default function ImportStatement({
    statement,
    summary,
    rows = [],
}: Props) {
    const [answers, setAnswers] = useState<Record<number, Answer>>({});
    const [showAll, setShowAll] = useState(false);
    const [changingColumns, setChangingColumns] = useState(false);
    const [importing, setImporting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const toAnswer = rows.filter((row) => row.status === 'answer');
    const answeredPayments = Object.values(answers).filter(
        (a) => a.kind === 'payment' && a.client.trim() !== '',
    ).length;
    const importCount = (summary?.payments ?? 0) + answeredPayments;
    const shown = showAll ? rows : rows.slice(0, 25);

    function answer(row: Row, next: Partial<Answer>) {
        setAnswers((current) => ({
            ...current,
            [row.index]: {
                kind: next.kind ?? current[row.index]?.kind ?? 'payment',
                client: next.client ?? current[row.index]?.client ?? '',
            },
        }));
    }

    function runImport() {
        setImporting(true);
        router.post(
            confirm().url,
            { answers },
            {
                onError: (e) => setErrors(e),
                onFinish: () => setImporting(false),
            },
        );
    }

    return (
        <>
            <Head title="Import payments" />

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
                        Import payments
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Bank, e-wallet or platform payout statement (CSV) · read
                        on this computer
                    </p>
                </div>
            </header>

            <div className="flex flex-col gap-5">
                {statement === null ? (
                    <UploadCard />
                ) : (
                    <>
                        <section className="flex flex-wrap items-center gap-4 rounded-card border bg-card p-5">
                            <FileText
                                className="size-6 shrink-0 text-primary"
                                aria-hidden
                            />
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-[17px] font-bold">
                                    {statement.name}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {plural(statement.lines, 'line')} read
                                    {statement.matched &&
                                        ` · columns matched: ${describeMapping(statement.columns, statement.mapping)}`}
                                    {statement.matched && (
                                        <>
                                            {' · '}
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setChangingColumns(
                                                        !changingColumns,
                                                    )
                                                }
                                                className="font-bold text-primary hover:underline"
                                            >
                                                Change columns
                                            </button>
                                        </>
                                    )}
                                </p>
                            </div>
                            <Button
                                variant="outline"
                                className="rounded-xl font-bold"
                                onClick={() => router.delete(cancel().url)}
                            >
                                Choose another file
                            </Button>
                        </section>

                        {(!statement.matched || changingColumns) && (
                            <ColumnsCard
                                columns={statement.columns}
                                mapping={statement.mapping}
                                onDone={() => setChangingColumns(false)}
                            />
                        )}

                        {statement.matched && summary && (
                            <>
                                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <Tile
                                        tone="success"
                                        label={`Client payments · ${plural(summary.clients, 'client')}`}
                                        value={`${summary.payments} · ${peso(summary.total)}`}
                                    />
                                    <Tile
                                        label="Not income (transfers, bills)"
                                        value={`${summary.ignored} ignored`}
                                    />
                                    <Tile
                                        label="Already recorded"
                                        value={`${summary.duplicates} skipped`}
                                        detail={
                                            summary.filed > 0
                                                ? `${summary.filed} in filed quarters left out`
                                                : undefined
                                        }
                                    />
                                    <Tile
                                        tone={
                                            summary.answers > 0
                                                ? 'warning'
                                                : undefined
                                        }
                                        label="Needs your answer"
                                        value={String(summary.answers)}
                                    />
                                </div>

                                {toAnswer.map((row) => {
                                    const a = answers[row.index];

                                    return (
                                        <section
                                            key={row.index}
                                            className="flex flex-col gap-3 rounded-card border border-warning-border bg-warning-soft p-5"
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-3">
                                                <p className="text-[15px]">
                                                    <strong>
                                                        {row.date
                                                            ? formatDate(
                                                                  row.date,
                                                              )
                                                            : '—'}
                                                    </strong>{' '}
                                                    · “{row.description}” ·{' '}
                                                    <strong>
                                                        {peso(row.received)}
                                                    </strong>
                                                </p>
                                                <div className="flex gap-2">
                                                    {(
                                                        [
                                                            'payment',
                                                            'personal',
                                                        ] as const
                                                    ).map((kind) => (
                                                        <Button
                                                            key={kind}
                                                            type="button"
                                                            variant="outline"
                                                            aria-pressed={
                                                                a?.kind === kind
                                                            }
                                                            onClick={() =>
                                                                answer(row, {
                                                                    kind,
                                                                })
                                                            }
                                                            className={cn(
                                                                'rounded-xl font-bold',
                                                                a?.kind ===
                                                                    kind &&
                                                                    'border-2 border-primary text-primary',
                                                            )}
                                                        >
                                                            {kind === 'payment'
                                                                ? 'Client payment'
                                                                : 'Personal, not income'}
                                                        </Button>
                                                    ))}
                                                </div>
                                            </div>
                                            {a?.kind === 'payment' && (
                                                <div className="flex max-w-md flex-col gap-1.5">
                                                    <Label
                                                        htmlFor={`client-${row.index}`}
                                                    >
                                                        Who paid you?
                                                    </Label>
                                                    <Input
                                                        id={`client-${row.index}`}
                                                        value={a.client}
                                                        onChange={(event) =>
                                                            answer(row, {
                                                                client: event
                                                                    .target
                                                                    .value,
                                                            })
                                                        }
                                                        className="h-11 rounded-xl bg-card"
                                                    />
                                                    <FieldError
                                                        message={
                                                            errors[
                                                                `answers.${row.index}.client`
                                                            ]
                                                        }
                                                    />
                                                </div>
                                            )}
                                        </section>
                                    );
                                })}

                                <SectionCard>
                                    <div className="-mx-1 overflow-x-auto">
                                        <table className="w-full text-sm tabular-nums">
                                            <thead>
                                                <tr className="border-b text-left text-xs font-bold tracking-[0.3px] text-slate-600 uppercase">
                                                    <th className="px-1.5 py-2">
                                                        Date
                                                    </th>
                                                    <th className="px-1.5 py-2">
                                                        From statement
                                                    </th>
                                                    <th className="px-1.5 py-2">
                                                        Matched to
                                                    </th>
                                                    <th className="px-1.5 py-2 text-right">
                                                        Amount
                                                    </th>
                                                    <th className="px-1.5 py-2">
                                                        Status
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {shown.map((row) => (
                                                    <ImportRow
                                                        key={row.index}
                                                        row={row}
                                                        answer={
                                                            answers[row.index]
                                                        }
                                                    />
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    {rows.length > shown.length && (
                                        <p className="mt-3 text-sm text-muted-foreground">
                                            Showing {shown.length} of{' '}
                                            {rows.length} lines ·{' '}
                                            <button
                                                type="button"
                                                onClick={() => setShowAll(true)}
                                                className="font-bold text-primary hover:underline"
                                            >
                                                Show all
                                            </button>
                                        </p>
                                    )}
                                </SectionCard>

                                <div className="flex flex-wrap justify-end gap-2.5">
                                    <Button
                                        variant="outline"
                                        className="h-12 rounded-xl px-6 text-[15px] font-bold"
                                        onClick={() =>
                                            router.delete(cancel().url)
                                        }
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        disabled={
                                            importCount === 0 || importing
                                        }
                                        onClick={runImport}
                                        className="h-12 rounded-xl px-6 text-[15px] font-bold"
                                    >
                                        {importing
                                            ? 'Importing…'
                                            : `Import ${plural(importCount, 'payment')}`}
                                    </Button>
                                </div>
                            </>
                        )}
                    </>
                )}

                <SectionCard title="Other things that happened">
                    <ul className="grid gap-x-6 sm:grid-cols-2 xl:grid-cols-4">
                        <li className="border-t border-divider py-3">
                            <Link
                                href={marketplace().url}
                                className="block hover:underline"
                            >
                                <span className="block text-[15px] font-bold text-primary">
                                    I sell on an online marketplace
                                </span>
                                <span className="text-[13px] text-muted-foreground">
                                    Import the payout report: sales, fees and
                                    the platform's withholding.
                                </span>
                            </Link>
                        </li>
                        <li className="border-t border-divider py-3">
                            <Link
                                href={loansCreate().url}
                                className="block hover:underline"
                            >
                                <span className="block text-[15px] font-bold text-primary">
                                    I signed a loan
                                </span>
                                <span className="text-[13px] text-muted-foreground">
                                    Documentary stamp tax may be due on Form
                                    2000.
                                </span>
                            </Link>
                        </li>
                        <li className="border-t border-divider py-3">
                            <Link
                                href={payroll().url}
                                className="block hover:underline"
                            >
                                <span className="block text-[15px] font-bold text-primary">
                                    I pay an employee
                                </span>
                                <span className="text-[13px] text-muted-foreground">
                                    Tax withheld on their pay, remitted monthly
                                    on the 1601-C.
                                </span>
                            </Link>
                        </li>
                        <li className="border-t border-divider py-3">
                            <Link
                                href={lettersCreate().url}
                                className="block hover:underline"
                            >
                                <span className="block text-[15px] font-bold text-primary">
                                    I got a letter from the BIR
                                </span>
                                <span className="text-[13px] text-muted-foreground">
                                    See what your records say, and what to send
                                    or bring.
                                </span>
                            </Link>
                        </li>
                        {OTHER_THINGS.map(([title, detail]) => (
                            <li
                                key={title}
                                className="border-t border-divider py-3"
                            >
                                <span className="block text-[15px] font-bold">
                                    {title}
                                </span>
                                <span className="text-[13px] text-muted-foreground">
                                    {detail}
                                </span>
                            </li>
                        ))}
                    </ul>
                </SectionCard>
            </div>
        </>
    );
}

function UploadCard() {
    const form = useForm<{ statement: File | null }>({ statement: null });

    return (
        <SectionCard title="Choose a statement">
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(upload());
                }}
                className="flex max-w-lg flex-col gap-3"
            >
                <p className="text-[15px] text-muted-foreground">
                    Download your statement as a CSV from your bank, e-wallet or
                    payout platform. We'll match payments to your clients, set
                    aside bills and transfers, and skip what you already
                    recorded. Nothing is uploaded anywhere.
                </p>
                <Input
                    type="file"
                    accept=".csv,text/csv,.txt"
                    aria-label="Statement CSV"
                    onChange={(event) =>
                        form.setData(
                            'statement',
                            event.target.files?.[0] ?? null,
                        )
                    }
                    className="h-11 rounded-xl bg-card py-2"
                />
                <FieldError message={form.errors.statement} />
                <Button
                    type="submit"
                    disabled={!form.data.statement || form.processing}
                    className="h-11 self-start rounded-xl px-5 font-bold"
                >
                    {form.processing ? 'Reading…' : 'Read statement'}
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
    const form = useForm({
        date: mapping.date === null ? '' : String(mapping.date),
        description:
            mapping.description === null ? '' : String(mapping.description),
        amount: mapping.amount === null ? '' : String(mapping.amount),
        credit: mapping.credit === null ? '' : String(mapping.credit),
        debit: mapping.debit === null ? '' : String(mapping.debit),
    });
    const fields: [keyof typeof form.data, string][] = [
        ['date', 'Date'],
        ['description', 'Description'],
        ['amount', 'Amount (in and out in one column)'],
        ['credit', 'Or: money in'],
        ['debit', 'And: money out'],
    ];

    return (
        <SectionCard title="Which columns are which?">
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(saveColumns(), { onSuccess: onDone });
                }}
                className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5"
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
                <div className="sm:col-span-2 lg:col-span-5">
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

function ImportRow({ row, answer }: { row: Row; answer?: Answer }) {
    const status: Status = row.status;
    const answered = status === 'answer' && answer;

    return (
        <tr className="border-b border-divider">
            <td className="px-1.5 py-2.5 whitespace-nowrap">
                {row.date ? formatDate(row.date) : '—'}
            </td>
            <td className="px-1.5 py-2.5 uppercase">{row.description}</td>
            <td className="px-1.5 py-2.5">
                {status === 'import' ||
                status === 'duplicate' ||
                status === 'filed' ? (
                    <span>
                        <span className="font-bold">{row.client}</span>
                        {row.reason && status === 'import' && (
                            <span className="block text-xs text-muted-foreground">
                                {row.reason}
                            </span>
                        )}
                    </span>
                ) : status === 'answer' ? (
                    <span className="font-bold text-warning">
                        {answered
                            ? answer.kind === 'payment'
                                ? answer.client || 'Who paid?'
                                : 'Personal'
                            : 'Needs answer'}
                    </span>
                ) : (
                    <span className="text-muted-foreground">
                        {status === 'ignore' ? 'Not income' : row.reason}
                    </span>
                )}
            </td>
            <td className="px-1.5 py-2.5 text-right whitespace-nowrap">
                {row.received < 0 ? '−' : ''}
                {peso(row.received)}
                {status === 'import' && row.withheld > 0 && (
                    <span className="block text-xs text-muted-foreground">
                        billed {peso(row.amount)}
                    </span>
                )}
            </td>
            <td
                className={cn(
                    'px-1.5 py-2.5 whitespace-nowrap',
                    status === 'import' && 'font-bold text-success',
                    status === 'answer' && 'font-bold text-warning',
                    !['import', 'answer'].includes(status) &&
                        'text-muted-foreground',
                )}
            >
                {answered
                    ? answer.kind === 'payment'
                        ? 'Import'
                        : 'Ignore'
                    : statusLabels[status]}
            </td>
        </tr>
    );
}

function Tile({
    label,
    value,
    detail,
    tone,
}: {
    label: string;
    value: string;
    detail?: string;
    tone?: 'success' | 'warning';
}) {
    return (
        <div
            className={cn(
                'rounded-card border bg-card p-4',
                tone === 'success' && 'border-green-200 bg-success-soft',
                tone === 'warning' && 'border-warning-border bg-warning-soft',
            )}
        >
            <p
                className={cn(
                    'text-[13px] font-bold text-muted-foreground',
                    tone === 'success' && 'text-success',
                    tone === 'warning' && 'text-warning',
                )}
            >
                {label}
            </p>
            <p className="text-2xl font-extrabold">{value}</p>
            {detail && (
                <p className="text-[13px] text-muted-foreground">{detail}</p>
            )}
        </div>
    );
}

function describeMapping(columns: string[], mapping: Mapping): string {
    const name = (i: number | null) =>
        i === null ? null : (columns[i] || `column ${i + 1}`).toLowerCase();

    return [
        name(mapping.date),
        name(mapping.description),
        mapping.amount !== null
            ? name(mapping.amount)
            : [name(mapping.credit), name(mapping.debit)]
                  .filter(Boolean)
                  .join(' / '),
    ]
        .filter(Boolean)
        .join(', ');
}
