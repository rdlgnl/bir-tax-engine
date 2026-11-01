import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { todayIso } from '@/lib/format';
import { filed } from '@/routes/corporation';
import type { CorporateQuarterTax } from '@/types';

/** "I've filed it": records the date, the reference and what was paid. */
export function MarkQuarterFiled({
    tax,
    label = "I've filed it · mark as filed",
}: {
    tax: CorporateQuarterTax;
    label?: string;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        filed_on: todayIso(),
        reference_number: '',
        amount_paid: String(Math.max(0, tax.payable)),
    });

    if (!open) {
        return (
            <Button
                onClick={() => setOpen(true)}
                className="h-12 rounded-xl text-[15px] font-bold"
            >
                {label}
            </Button>
        );
    }

    return (
        <SectionCard>
            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(filed([tax.year, tax.quarter]), {
                        preserveScroll: true,
                    });
                }}
                className="flex flex-col gap-3"
            >
                <div className="grid grid-cols-2 gap-3">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="filed_on" className="font-bold">
                            Date filed
                        </Label>
                        <Input
                            id="filed_on"
                            type="date"
                            max={todayIso()}
                            value={form.data.filed_on}
                            onChange={(event) =>
                                form.setData('filed_on', event.target.value)
                            }
                            className="h-11 rounded-xl"
                        />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="amount_paid" className="font-bold">
                            Amount paid
                        </Label>
                        <MoneyInput
                            id="amount_paid"
                            value={form.data.amount_paid}
                            onValueChange={(value) =>
                                form.setData('amount_paid', value)
                            }
                        />
                    </div>
                </div>
                <Input
                    aria-label="Reference no."
                    placeholder="Reference no. (optional)"
                    value={form.data.reference_number}
                    onChange={(event) =>
                        form.setData('reference_number', event.target.value)
                    }
                    className="h-11 rounded-xl"
                />
                <FieldError
                    message={form.errors.filed_on ?? form.errors.amount_paid}
                />
                <Button
                    type="submit"
                    disabled={form.processing}
                    className="h-11 rounded-xl font-bold"
                >
                    Mark Q{tax.quarter} 1702Q filed
                </Button>
            </form>
        </SectionCard>
    );
}
