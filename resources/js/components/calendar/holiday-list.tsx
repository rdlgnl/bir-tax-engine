import { router, useForm } from '@inertiajs/react';
import { FieldError } from '@/components/field-error';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { off, reset, store, undo } from '@/routes/calendar/holidays';

export type HolidayRow = {
    date: string;
    name: string;
    source: 'proclaimed' | 'estimated' | 'yours';
    /** The taxpayer renamed it, added it or turned it off. */
    changed: boolean;
    /** A listed day the taxpayer says isn't a holiday this year. */
    off: boolean;
};

const sourceLabel: Record<HolidayRow['source'], string> = {
    proclaimed: 'Proclaimed',
    estimated: 'Estimated',
    yours: 'Added by you',
};

/**
 * A year's holidays, which move deadlines to the next working day. The
 * taxpayer can add a day, rename one or turn one off when a proclamation
 * differs from the list the app ships, and undo any of it.
 */
export function HolidayList({
    year,
    rows,
}: {
    year: number;
    rows: HolidayRow[];
}) {
    const form = useForm({ date: '', name: '' });
    const keep = { preserveScroll: true };

    return (
        <SectionCard
            title={`${year} holidays`}
            action={
                rows.some((row) => row.changed) && (
                    <Button
                        variant="ghost"
                        size="xs"
                        onClick={() => router.delete(reset(year).url, keep)}
                    >
                        Reset
                    </Button>
                )
            }
        >
            <ul className="flex flex-col divide-y divide-divider">
                {rows.map((row) => (
                    <li
                        key={row.date}
                        className="flex items-center gap-3 py-2 text-sm"
                    >
                        <span className="w-14 shrink-0 font-extrabold">
                            {formatDate(row.date)}
                        </span>
                        <span className="min-w-0 flex-1">
                            <span
                                className={
                                    row.off
                                        ? 'text-muted-foreground line-through'
                                        : ''
                                }
                            >
                                {row.name}
                            </span>
                            <span className="block text-xs text-muted-foreground">
                                {row.off
                                    ? 'Not a holiday this year'
                                    : row.changed && row.source !== 'yours'
                                      ? `Changed by you · ${sourceLabel[row.source].toLowerCase()}`
                                      : sourceLabel[row.source]}
                            </span>
                        </span>
                        {row.changed && row.source !== 'yours' ? (
                            <Button
                                variant="ghost"
                                size="xs"
                                onClick={() =>
                                    router.delete(undo(row.date).url, keep)
                                }
                            >
                                Undo
                            </Button>
                        ) : (
                            <Button
                                variant="ghost"
                                size="xs"
                                aria-label={`${row.name} isn't a holiday`}
                                onClick={() =>
                                    router.post(off(row.date).url, {}, keep)
                                }
                            >
                                Remove
                            </Button>
                        )}
                    </li>
                ))}
            </ul>

            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(store(), {
                        ...keep,
                        onSuccess: () => form.reset(),
                    });
                }}
                className="mt-3 flex flex-col gap-2 border-t border-divider pt-3"
            >
                <span className="text-sm font-bold">
                    Add or rename a holiday
                </span>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="holiday-date" className="sr-only">
                        Day
                    </Label>
                    <Input
                        id="holiday-date"
                        type="date"
                        min={`${year}-01-01`}
                        max={`${year}-12-31`}
                        value={form.data.date}
                        onChange={(event) =>
                            form.setData('date', event.target.value)
                        }
                        className="h-10 rounded-xl"
                    />
                    <FieldError message={form.errors.date} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="holiday-name" className="sr-only">
                        Name
                    </Label>
                    <Input
                        id="holiday-name"
                        placeholder="Name, as the proclamation has it"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        className="h-10 rounded-xl"
                    />
                    <FieldError message={form.errors.name} />
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    disabled={form.processing}
                    className="h-10 rounded-xl font-bold"
                >
                    Save holiday
                </Button>
                <p className="text-xs text-muted-foreground">
                    Deadlines on a holiday move to the next working day. For a
                    holiday that was moved, remove the old day and add the new
                    one.
                </p>
            </form>
        </SectionCard>
    );
}
