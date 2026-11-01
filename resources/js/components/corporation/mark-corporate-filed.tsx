import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { todayIso } from '@/lib/format';
import type { RouteDefinition } from '@/wayfinder';

/** "I've filed it": records a 1702Q or 1702-RT's date, reference and payment. */
export function MarkCorporateFiled({
    action,
    payable,
    form: formName,
    label = "I've filed it · mark as filed",
}: {
    action: RouteDefinition<'post'>;
    payable: number;
    /** "Q3 1702Q", "2026 1702-RT" */
    form: string;
    label?: string;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        filed_on: todayIso(),
        reference_number: '',
        amount_paid: String(Math.max(0, payable)),
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
                    form.submit(action, {
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
                    Mark {formName} filed
                </Button>
            </form>
        </SectionCard>
    );
}
