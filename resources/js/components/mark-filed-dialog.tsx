import { useForm } from '@inertiajs/react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { peso, todayIso } from '@/lib/format';
import { store } from '@/routes/filings';
import type { QuarterResult } from '@/types';

type Channel = 'online' | 'bank' | 'bir_office';

type FilingForm = {
    year: number;
    quarter: number;
    form: string;
    filed_on: string;
    reference_number: string;
    paid: boolean;
    amount_paid: string;
    paid_on: string;
    payment_channel: Channel;
};

type Props = {
    year: number;
    quarter: QuarterResult;
    trigger: React.ReactNode;
};

/** "Done filing?": records the return, which locks the quarter. */
export function MarkFiledDialog({ year, quarter, trigger }: Props) {
    const name =
        quarter.quarter === 4
            ? `the ${year} annual return`
            : `Q${quarter.quarter}`;
    const form = useForm<FilingForm>({
        year,
        quarter: quarter.quarter,
        form: quarter.form,
        filed_on: todayIso(),
        reference_number: '',
        paid: true,
        amount_paid: String(quarter.due),
        paid_on: todayIso(),
        payment_channel: 'online',
    });

    function submit(paid: boolean) {
        form.transform((data) => ({ ...data, paid }));
        form.submit(store(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[92dvh] max-w-[480px] overflow-y-auto rounded-[22px]">
                <DialogTitle className="text-xl font-extrabold">
                    Done filing?
                </DialogTitle>
                <DialogDescription>
                    Q{quarter.quarter === 4 ? '4 / annual' : quarter.quarter}{' '}
                    {year} · {quarter.form} · {peso(quarter.due)}
                </DialogDescription>

                <form
                    noValidate
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit(true);
                    }}
                    className="flex flex-col gap-4"
                >
                    <fieldset className="flex flex-col gap-3">
                        <legend className="mb-2 text-sm font-extrabold">
                            1 · Filing
                        </legend>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="filed_on">Date filed</Label>
                                <Input
                                    id="filed_on"
                                    type="date"
                                    max={todayIso()}
                                    value={form.data.filed_on}
                                    onChange={(event) =>
                                        form.setData(
                                            'filed_on',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="reference_number">
                                    Reference no.
                                </Label>
                                <Input
                                    id="reference_number"
                                    value={form.data.reference_number}
                                    onChange={(event) =>
                                        form.setData(
                                            'reference_number',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <FieldError
                            message={
                                form.errors.filed_on ??
                                form.errors.form ??
                                form.errors.quarter
                            }
                        />
                    </fieldset>

                    <fieldset className="flex flex-col gap-3">
                        <legend className="mb-2 text-sm font-extrabold">
                            2 · Payment
                        </legend>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="amount_paid">Amount paid</Label>
                                <MoneyInput
                                    id="amount_paid"
                                    value={form.data.amount_paid}
                                    onValueChange={(value) =>
                                        form.setData('amount_paid', value)
                                    }
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="paid_on">Date paid</Label>
                                <Input
                                    id="paid_on"
                                    type="date"
                                    max={todayIso()}
                                    value={form.data.paid_on}
                                    onChange={(event) =>
                                        form.setData(
                                            'paid_on',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <FieldError
                            message={
                                form.errors.amount_paid ?? form.errors.paid_on
                            }
                        />
                        <span className="text-sm font-semibold">
                            Paid through
                        </span>
                        <ChoiceChips<Channel>
                            label="Paid through"
                            value={form.data.payment_channel}
                            onChange={(value) =>
                                form.setData('payment_channel', value)
                            }
                            options={[
                                {
                                    value: 'online',
                                    label: 'Online bank or e-wallet',
                                },
                                { value: 'bank', label: 'Bank counter' },
                                { value: 'bir_office', label: 'BIR office' },
                            ]}
                        />
                        {Number(form.data.amount_paid) === quarter.due && (
                            <p className="text-[13px] font-semibold text-success">
                                Matches the amount due
                            </p>
                        )}
                    </fieldset>

                    <p className="rounded-xl bg-muted p-3 text-[13px] text-muted-foreground">
                        After saving, {name} is locked. Later changes become an
                        amendment, with the original kept in your history.
                    </p>

                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="h-12 rounded-xl text-base font-bold"
                    >
                        Mark {name} as filed and paid
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        disabled={form.processing}
                        onClick={() => submit(false)}
                    >
                        Only filed so far, I'll pay later
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
