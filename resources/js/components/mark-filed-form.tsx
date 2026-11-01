import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { todayIso } from '@/lib/format';
import { store } from '@/routes/filings';

type Channel = 'online' | 'bank' | 'bir_office';

type FilingForm = {
    year: number;
    quarter: number;
    month: number | null;
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
    quarter: number;
    /** For a monthly form (0619-E). */
    month?: number | null;
    form: string;
    amountDue: number;
};

/** "Done? Mark it filed and paid": records the return and freezes its figures. */
export function MarkFiledForm({
    year,
    quarter,
    month = null,
    form: formName,
    amountDue,
}: Props) {
    const name =
        month !== null
            ? new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
                  new Date(year, month - 1, 1),
              )
            : quarter === 4 && !['2551Q', '2550Q', '1601-EQ'].includes(formName)
              ? `the ${year} annual return`
              : `Q${quarter}`;
    const [sameNumbers, setSameNumbers] = useState(true);
    const form = useForm<FilingForm>({
        year,
        quarter,
        month,
        form: formName,
        filed_on: todayIso(),
        reference_number: '',
        paid: true,
        amount_paid: String(amountDue),
        paid_on: todayIso(),
        payment_channel: 'online',
    });

    function submit(paid: boolean) {
        form.transform((data) => ({ ...data, paid }));
        form.submit(store(), { preserveScroll: true });
    }

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                submit(true);
            }}
            className="flex flex-col gap-4"
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
            <FieldError
                message={
                    form.errors.filed_on ??
                    form.errors.amount_paid ??
                    form.errors.form ??
                    form.errors.quarter
                }
            />

            <div className="flex flex-col gap-1.5">
                <Label htmlFor="reference_number" className="font-bold">
                    Reference no.
                </Label>
                <Input
                    id="reference_number"
                    placeholder="From the confirmation email"
                    value={form.data.reference_number}
                    onChange={(event) =>
                        form.setData('reference_number', event.target.value)
                    }
                    className="h-11 rounded-xl"
                />
            </div>

            <div className="grid grid-cols-2 gap-3">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="paid_on" className="font-bold">
                        Date paid
                    </Label>
                    <Input
                        id="paid_on"
                        type="date"
                        max={todayIso()}
                        value={form.data.paid_on}
                        onChange={(event) =>
                            form.setData('paid_on', event.target.value)
                        }
                        className="h-11 rounded-xl"
                    />
                </div>
            </div>
            <FieldError message={form.errors.paid_on} />

            <div className="flex flex-col gap-1.5">
                <span className="text-sm font-bold">Paid through</span>
                <ChoiceChips<Channel>
                    label="Paid through"
                    value={form.data.payment_channel}
                    onChange={(value) => form.setData('payment_channel', value)}
                    options={[
                        { value: 'online', label: 'Online bank or e-wallet' },
                        { value: 'bank', label: 'Bank counter' },
                        { value: 'bir_office', label: 'BIR office' },
                    ]}
                />
            </div>

            <label className="flex items-center gap-3 text-sm">
                <Checkbox
                    checked={sameNumbers}
                    onCheckedChange={(checked) =>
                        setSameNumbers(checked === true)
                    }
                />
                I used the same numbers shown here
            </label>
            {!sameNumbers && (
                <p className="text-[13px] text-warning">
                    If you filed different numbers, mark it filed anyway and fix
                    your records; the amended return that reconciles them comes
                    in a later update.
                </p>
            )}

            <p className="text-[13px] text-muted-foreground">
                After saving, {name} locks and its figures are kept as filed.
                Later changes become an amendment.
            </p>

            <Button
                type="submit"
                disabled={form.processing}
                className="h-12 rounded-xl text-base font-bold"
            >
                Mark {name} filed and paid
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
    );
}
