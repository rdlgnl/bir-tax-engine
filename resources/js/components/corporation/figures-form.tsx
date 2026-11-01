import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { save } from '@/routes/corporation/quarter';
import type { CorporateQuarterFigures } from '@/types';

const FIELDS: {
    key: keyof CorporateQuarterFigures;
    label: string;
    hint?: string;
}[] = [
    { key: 'sales', label: 'Sales, receipts and fees' },
    { key: 'cost_of_sales', label: 'Cost of sales or services' },
    {
        key: 'other_income',
        label: 'Other taxable income',
        hint: 'Not income already taxed at source with a final tax',
    },
    { key: 'deductions', label: 'Deductions (operating expenses)' },
    {
        key: 'withheld',
        label: 'Withheld by clients this quarter',
        hint: 'The 2307s received for the quarter',
    },
];

/** The quarter's own figures from the books, not the year to date. */
export function FiguresForm({
    year,
    quarter,
    figures,
    osd,
    startOpen = false,
}: {
    year: number;
    quarter: number;
    figures: CorporateQuarterFigures | null;
    osd: boolean;
    startOpen?: boolean;
}) {
    const [open, setOpen] = useState(startOpen);
    const form = useForm<CorporateQuarterFigures>({
        sales: figures?.sales ?? '',
        cost_of_sales: figures?.cost_of_sales ?? '0',
        other_income: figures?.other_income ?? '0',
        deductions: figures?.deductions ?? '0',
        withheld: figures?.withheld ?? '0',
    });

    if (!open) {
        return (
            <Button
                variant="outline"
                onClick={() => setOpen(true)}
                className="h-11 self-start rounded-xl font-bold"
            >
                Edit Q{quarter} figures
            </Button>
        );
    }

    return (
        <SectionCard title={`Q${quarter} ${year} figures from the books`}>
            <p className="mb-4 text-sm text-muted-foreground">
                This quarter alone (
                {['Jan–Mar', 'Apr–Jun', 'Jul–Sep', 'Oct–Dec'][quarter - 1]}
                ), as the corporation's books or accountant have them. The app
                adds up the year to date.
            </p>
            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(save([year, quarter]), {
                        preserveScroll: true,
                        onSuccess: () => setOpen(startOpen),
                    });
                }}
                className="grid gap-4 sm:grid-cols-2"
            >
                {FIELDS.filter(
                    (field) => !(osd && field.key === 'deductions'),
                ).map((field) => (
                    <div key={field.key} className="flex flex-col gap-1.5">
                        <Label htmlFor={field.key} className="font-bold">
                            {field.label}
                        </Label>
                        <MoneyInput
                            id={field.key}
                            value={form.data[field.key]}
                            onValueChange={(value) =>
                                form.setData(field.key, value)
                            }
                        />
                        {field.hint && (
                            <span className="text-[13px] text-muted-foreground">
                                {field.hint}
                            </span>
                        )}
                        <FieldError message={form.errors[field.key]} />
                    </div>
                ))}
                {osd && (
                    <p className="text-sm text-muted-foreground sm:col-span-2">
                        On the optional standard deduction, 40% of gross income
                        is deducted in place of the expenses.
                    </p>
                )}
                <Button
                    type="submit"
                    disabled={form.processing}
                    className="h-11 rounded-xl font-bold sm:col-span-2"
                >
                    Save Q{quarter} figures
                </Button>
            </form>
        </SectionCard>
    );
}
