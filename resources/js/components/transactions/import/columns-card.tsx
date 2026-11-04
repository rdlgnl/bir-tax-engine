import { useForm } from '@inertiajs/react';
import { FieldError } from '@/components/field-error';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { columns as saveColumns } from '@/routes/transactions/import';
import type { Mapping } from './types';

/** Telling the app which column of the statement is which. */
export function ColumnsCard({
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

/** The columns matched, in words: "date, details, credit / debit". */
export function describeMapping(columns: string[], mapping: Mapping): string {
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
