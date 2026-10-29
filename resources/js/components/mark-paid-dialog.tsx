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
import { formatDate, peso, todayIso } from '@/lib/format';
import { update } from '@/routes/filings';
import type { FilingSummary } from '@/types';

type Channel = 'online' | 'bank' | 'bir_office';

type Props = {
    filing: FilingSummary;
    amountDue: number;
    trigger: React.ReactNode;
};

/** For a return that was filed first and paid later. */
export function MarkPaidDialog({ filing, amountDue, trigger }: Props) {
    const form = useForm<{
        amount_paid: string;
        paid_on: string;
        payment_channel: Channel;
    }>({
        amount_paid: String(amountDue),
        paid_on: todayIso(),
        payment_channel: 'online',
    });

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-w-[480px] rounded-[22px]">
                <DialogTitle className="text-xl font-extrabold">
                    Record your payment
                </DialogTitle>
                <DialogDescription>
                    Filed {formatDate(filing.filed_on, 'long')} ·{' '}
                    {peso(amountDue)} due
                </DialogDescription>
                <form
                    noValidate
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit(update(filing.id), {
                            preserveScroll: true,
                        });
                    }}
                    className="flex flex-col gap-4"
                >
                    <div className="grid grid-cols-2 gap-3">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="pay-amount">Amount paid</Label>
                            <MoneyInput
                                id="pay-amount"
                                value={form.data.amount_paid}
                                onValueChange={(value) =>
                                    form.setData('amount_paid', value)
                                }
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="pay-date">Date paid</Label>
                            <Input
                                id="pay-date"
                                type="date"
                                max={todayIso()}
                                value={form.data.paid_on}
                                onChange={(event) =>
                                    form.setData('paid_on', event.target.value)
                                }
                            />
                        </div>
                    </div>
                    <FieldError
                        message={form.errors.amount_paid ?? form.errors.paid_on}
                    />
                    <span className="text-sm font-semibold">Paid through</span>
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
                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="h-12 rounded-xl text-base font-bold"
                    >
                        Mark paid
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
