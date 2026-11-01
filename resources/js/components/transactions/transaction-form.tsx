import { router, useForm } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import {
    AgentWithholding,
    agentWithheld,
} from '@/components/transactions/agent-withholding';
import { PreviewBox } from '@/components/transactions/preview-box';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTransactionPreview } from '@/hooks/use-transaction-preview';
import { formatDate, peso, todayIso } from '@/lib/format';
import { destroy, store, update } from '@/routes/transactions';
import type {
    ExpenseCategory,
    TransactionForm as FormData,
    TransactionFormContext,
    TransactionType,
    TransactionWithEffect,
    VatKind,
} from '@/types';

const categories: { value: ExpenseCategory; label: string }[] = [
    { value: 'internet', label: 'Internet & phone' },
    { value: 'rent', label: 'Rent' },
    { value: 'equipment', label: 'Equipment' },
    { value: 'software', label: 'Software' },
    { value: 'transport', label: 'Transport' },
    { value: 'person', label: 'Paid a person' },
    { value: 'other', label: 'Other' },
];

type Props = {
    type: TransactionType;
    context: TransactionFormContext;
    editing?: TransactionWithEffect | null;
    onDone?: () => void;
};

export function TransactionForm({
    type,
    context,
    editing = null,
    onDone,
}: Props) {
    const form = useForm<FormData>({
        type,
        occurred_on: editing?.occurred_on ?? todayIso(),
        counterparty: editing?.counterparty ?? '',
        amount: editing ? String(editing.amount) : '',
        tax_withheld:
            editing && editing.tax_withheld > 0
                ? String(editing.tax_withheld)
                : '',
        certificate_received_on: editing?.certificate_received_on ?? null,
        invoice_number: editing?.invoice_number ?? '',
        invoice_paid: editing?.invoice_paid ?? false,
        category: editing?.category ?? '',
        has_valid_invoice: editing?.has_valid_invoice ?? false,
        description: editing?.description ?? '',
        vat_kind: editing?.vat_kind ?? 'vatable',
        vat_amount:
            editing && editing.vat_amount > 0 ? String(editing.vat_amount) : '',
        counterparty_tin: editing?.counterparty_tin ?? '',
        atc: editing?.atc ?? '',
        agent_withheld: '',
    });
    // VAT is 12% of the amount until the person types their own figure.
    const [vatTouched, setVatTouched] = useState(
        Boolean(editing && editing.vat_amount > 0),
    );
    const [showsVat, setShowsVat] = useState(
        Boolean(editing && editing.vat_amount > 0),
    );
    const [withheld, setWithheld] = useState<boolean>(
        editing ? editing.tax_withheld > 0 : false,
    );
    const [confirm, setConfirm] = useState<
        'duplicate' | 'unusual' | 'delete' | null
    >(null);
    const { preview, loading } = useTransactionPreview(
        { ...form.data, tax_withheld: withheld ? form.data.tax_withheld : '0' },
        editing?.id ?? null,
    );

    const asksWithholding =
        type === 'payment' || (type === 'invoice' && form.data.invoice_paid);
    const vatRate = context.vatRate;
    // Only a withholding agent keeps tax from what they pay others.
    const withholdingAtcs = type === 'expense' ? context.withholdingAtcs : null;
    const vatApplies =
        vatRate !== null &&
        (type === 'expense' ? showsVat : form.data.vat_kind === 'vatable');
    const autoVat =
        vatRate === null
            ? 0
            : Math.round(Number(form.data.amount || 0) * vatRate * 100) / 100;
    const vatAmount = !vatApplies
        ? '0'
        : vatTouched
          ? form.data.vat_amount
          : String(autoVat);
    const expected =
        Math.round(
            Number(form.data.amount || 0) * context.expectedWithholdingPercent,
        ) / 100;
    const field = (name: keyof FormData) => ({
        id: `tx-${name}`,
        'aria-invalid': form.errors[name] ? true : undefined,
        'aria-describedby': form.errors[name] ? `tx-${name}-error` : undefined,
    });

    function save(checked = false) {
        if (!checked && !editing && preview?.duplicate) {
            return setConfirm('duplicate');
        }

        if (!checked && preview?.unusual_multiple) {
            return setConfirm('unusual');
        }

        setConfirm(null);
        form.transform((data) => ({
            ...data,
            vat_amount: vatAmount,
            atc: withholdingAtcs ? data.atc : '',
            agent_withheld: withholdingAtcs
                ? String(
                      agentWithheld(
                          withholdingAtcs,
                          data.atc,
                          Number(data.amount || 0),
                      ),
                  )
                : '0',
            tax_withheld: asksWithholding && withheld ? data.tax_withheld : '0',
            certificate_received_on:
                asksWithholding && withheld
                    ? data.certificate_received_on
                    : null,
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                if (!editing) {
                    form.reset(
                        'amount',
                        'tax_withheld',
                        'invoice_number',
                        'description',
                        'certificate_received_on',
                    );
                    setWithheld(false);
                }

                onDone?.();
            },
        };

        if (editing) {
            form.submit(update(editing.id), options);
        } else {
            form.submit(store(), options);
        }
    }

    function remove() {
        if (!editing) {
            return;
        }

        router.delete(destroy(editing.id).url, {
            preserveScroll: true,
            onSuccess: () => onDone?.(),
            onFinish: () => setConfirm(null),
        });
    }

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                save();
            }}
            className="flex flex-col gap-4"
        >
            {type === 'invoice' && (
                <p className="text-[13px] text-muted-foreground">
                    A sale counts on the day you invoice, even before you're
                    paid. Use your BIR-registered invoices, not "Official
                    Receipts".
                </p>
            )}
            {type === 'expense' && context.regime === 'eight_percent' && (
                <p className="text-[13px] text-muted-foreground">
                    You're on flat 8%, so expenses don't lower your tax.
                    Recording them is optional, for your own books.
                </p>
            )}

            <div className="flex flex-col gap-1.5">
                <Label htmlFor="tx-amount" className="text-sm font-bold">
                    {type === 'payment'
                        ? 'Amount billed'
                        : type === 'invoice'
                          ? 'Invoice amount'
                          : 'How much?'}
                    {vatRate !== null && (
                        <span className="font-normal text-muted-foreground">
                            {' '}
                            (before VAT)
                        </span>
                    )}
                </Label>
                <MoneyInput
                    {...field('amount')}
                    size="large"
                    value={form.data.amount}
                    onValueChange={(value) => form.setData('amount', value)}
                    placeholder="0"
                    autoFocus={!editing}
                />
                {type === 'payment' && !form.errors.amount && (
                    <p className="text-[13px] text-muted-foreground">
                        The full amount before anything was deducted.
                    </p>
                )}
                <FieldError id="tx-amount-error" message={form.errors.amount} />
            </div>

            {vatRate !== null && (
                <div className="flex flex-col gap-2 rounded-2xl bg-muted p-4">
                    {type === 'expense' ? (
                        <label className="flex items-center gap-3 text-sm font-bold">
                            <Checkbox
                                checked={showsVat}
                                onCheckedChange={(checked) =>
                                    setShowsVat(checked === true)
                                }
                            />
                            The invoice shows VAT
                        </label>
                    ) : (
                        <ChoiceChips<VatKind>
                            label="VAT on this sale"
                            variant="segmented"
                            value={form.data.vat_kind}
                            onChange={(value) =>
                                form.setData('vat_kind', value)
                            }
                            options={[
                                {
                                    value: 'vatable',
                                    label: `Vatable ${Math.round(vatRate * 100)}%`,
                                },
                                { value: 'zero_rated', label: 'Zero-rated' },
                                { value: 'exempt', label: 'Exempt' },
                            ]}
                        />
                    )}
                    {vatApplies && (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="tx-vat_amount"
                                className="text-sm font-bold"
                            >
                                VAT{' '}
                                <span className="font-normal text-muted-foreground">
                                    ({Math.round(vatRate * 100)}% unless the
                                    invoice says otherwise)
                                </span>
                            </Label>
                            <MoneyInput
                                {...field('vat_amount')}
                                value={vatAmount}
                                onValueChange={(value) => {
                                    setVatTouched(true);
                                    form.setData('vat_amount', value);
                                }}
                                placeholder="0"
                            />
                            <p className="text-[13px] text-muted-foreground">
                                Invoice total{' '}
                                {peso(
                                    Number(form.data.amount || 0) +
                                        Number(vatAmount || 0),
                                )}
                                {type === 'expense' &&
                                    !form.data.has_valid_invoice &&
                                    ' · claimable only if the invoice is in your name'}
                            </p>
                            <FieldError
                                id="tx-vat_amount-error"
                                message={form.errors.vat_amount}
                            />
                        </div>
                    )}
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="tx-counterparty_tin"
                            className="text-sm font-bold"
                        >
                            {type === 'expense' ? 'Supplier' : 'Client'}'s TIN{' '}
                            <span className="font-normal text-muted-foreground">
                                (optional, for your VAT lists)
                            </span>
                        </Label>
                        <Input
                            {...field('counterparty_tin')}
                            value={form.data.counterparty_tin}
                            onChange={(event) =>
                                form.setData(
                                    'counterparty_tin',
                                    event.target.value,
                                )
                            }
                            placeholder="000-000-000-000"
                            className="h-11 rounded-xl bg-card"
                        />
                        <FieldError
                            id="tx-counterparty_tin-error"
                            message={form.errors.counterparty_tin}
                        />
                    </div>
                </div>
            )}

            {type === 'invoice' && (
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="tx-invoice_number"
                        className="text-sm font-bold"
                    >
                        Invoice no.
                    </Label>
                    <Input
                        {...field('invoice_number')}
                        value={form.data.invoice_number}
                        onChange={(event) =>
                            form.setData('invoice_number', event.target.value)
                        }
                        className="h-11 rounded-xl bg-card"
                    />
                    <FieldError
                        id="tx-invoice_number-error"
                        message={form.errors.invoice_number}
                    />
                </div>
            )}

            <div className="grid grid-cols-2 gap-3">
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="tx-counterparty"
                        className="text-sm font-bold"
                    >
                        {type === 'payment'
                            ? 'From'
                            : type === 'invoice'
                              ? 'Billed to'
                              : 'Paid to'}
                    </Label>
                    <Input
                        {...field('counterparty')}
                        value={form.data.counterparty}
                        onChange={(event) =>
                            form.setData('counterparty', event.target.value)
                        }
                        autoComplete="off"
                        className="h-11 rounded-xl bg-card"
                    />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="tx-occurred_on"
                        className="text-sm font-bold"
                    >
                        {type === 'invoice' ? 'Date issued' : 'Date'}
                    </Label>
                    <Input
                        {...field('occurred_on')}
                        type="date"
                        max={todayIso()}
                        value={form.data.occurred_on}
                        onChange={(event) =>
                            form.setData('occurred_on', event.target.value)
                        }
                        className="h-11 rounded-xl bg-card"
                    />
                </div>
                <FieldError
                    id="tx-counterparty-error"
                    message={form.errors.counterparty}
                />
                <FieldError
                    id="tx-occurred_on-error"
                    message={form.errors.occurred_on}
                />
            </div>

            {type !== 'invoice' && (
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="tx-invoice_number"
                        className="text-sm font-bold"
                    >
                        {type === 'payment' ? 'Invoice no.' : 'Receipt no.'}{' '}
                        <span className="font-normal text-muted-foreground">
                            (optional, for your books)
                        </span>
                    </Label>
                    <Input
                        {...field('invoice_number')}
                        value={form.data.invoice_number}
                        onChange={(event) =>
                            form.setData('invoice_number', event.target.value)
                        }
                        autoComplete="off"
                        className="h-11 rounded-xl bg-card"
                    />
                    <FieldError
                        id="tx-invoice_number-error"
                        message={form.errors.invoice_number}
                    />
                </div>
            )}

            {type !== 'expense' &&
                context.recentCounterparties.length > 0 &&
                !editing && (
                    <div className="-mt-1 flex flex-wrap items-center gap-2">
                        <span className="text-[13px] text-muted-foreground">
                            Recent:
                        </span>
                        {context.recentCounterparties.map((name) => (
                            <button
                                key={name}
                                type="button"
                                onClick={() =>
                                    form.setData('counterparty', name)
                                }
                                className="rounded-full bg-muted px-3 py-1.5 text-[13px] font-semibold hover:bg-divider"
                            >
                                {name}
                            </button>
                        ))}
                    </div>
                )}

            {type === 'invoice' && (
                <>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="tx-description"
                            className="text-sm font-bold"
                        >
                            For what?{' '}
                            <span className="font-normal text-muted-foreground">
                                (optional)
                            </span>
                        </Label>
                        <Input
                            id="tx-description"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            className="h-11 rounded-xl bg-card"
                        />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <span className="text-sm font-bold">
                            Has it been paid?
                        </span>
                        <ChoiceChips
                            label="Has it been paid?"
                            variant="segmented"
                            value={form.data.invoice_paid ? 'yes' : 'no'}
                            onChange={(value) =>
                                form.setData('invoice_paid', value === 'yes')
                            }
                            options={[
                                { value: 'no', label: 'Not yet' },
                                { value: 'yes', label: 'Yes, paid' },
                            ]}
                        />
                    </div>
                </>
            )}

            {type === 'expense' && (
                <>
                    <div className="flex flex-col gap-1.5">
                        <span className="text-sm font-bold">
                            What was it for?
                        </span>
                        <ChoiceChips
                            label="What was it for?"
                            value={form.data.category || null}
                            onChange={(value) =>
                                form.setData('category', value)
                            }
                            options={categories}
                        />
                        <FieldError message={form.errors.category} />
                    </div>
                    <label className="flex items-start gap-3 rounded-xl border border-dashed border-input p-3.5">
                        <Checkbox
                            checked={form.data.has_valid_invoice}
                            onCheckedChange={(checked) =>
                                form.setData(
                                    'has_valid_invoice',
                                    checked === true,
                                )
                            }
                            className="mt-0.5"
                        />
                        <span className="text-sm">
                            <span className="font-bold">
                                The receipt says "Invoice" and is in my name
                            </span>
                            <span className="block text-[13px] text-muted-foreground">
                                If you switch to graduated rates later, only
                                invoices in your name count as deductions.
                            </span>
                        </span>
                    </label>
                    {withholdingAtcs && (
                        <AgentWithholding
                            atcs={withholdingAtcs}
                            atc={form.data.atc}
                            onChange={(atc) => form.setData('atc', atc)}
                            amount={Number(form.data.amount || 0)}
                            payee={form.data.counterparty}
                            error={
                                form.errors.atc ?? form.errors.agent_withheld
                            }
                        />
                    )}
                </>
            )}

            {asksWithholding && (
                <div className="flex flex-col gap-2">
                    <span className="text-sm font-bold">
                        Did they deduct tax before paying you?
                    </span>
                    <ChoiceChips
                        label="Did they deduct tax before paying you?"
                        variant="segmented"
                        value={withheld ? 'yes' : 'no'}
                        onChange={(value) => setWithheld(value === 'yes')}
                        options={[
                            { value: 'yes', label: 'Yes' },
                            { value: 'no', label: 'No, paid in full' },
                        ]}
                    />
                    {withheld && (
                        <div className="mt-1 flex flex-col gap-2 rounded-2xl bg-muted p-4">
                            <Label
                                htmlFor="tx-tax_withheld"
                                className="text-sm font-bold"
                            >
                                Tax they withheld{' '}
                                <span className="font-normal text-muted-foreground">
                                    ({context.expectedWithholdingPercent}%
                                    expected)
                                </span>
                            </Label>
                            <MoneyInput
                                {...field('tax_withheld')}
                                value={form.data.tax_withheld}
                                onValueChange={(value) =>
                                    form.setData('tax_withheld', value)
                                }
                                placeholder={
                                    expected > 0 ? String(expected) : '0'
                                }
                                trailing={
                                    expected > 0 && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                form.setData(
                                                    'tax_withheld',
                                                    String(expected),
                                                )
                                            }
                                            className="rounded-full bg-accent px-2.5 py-1 text-xs font-bold text-accent-foreground"
                                        >
                                            Use {peso(expected)}
                                        </button>
                                    )
                                }
                            />
                            <FieldError
                                id="tx-tax_withheld-error"
                                message={form.errors.tax_withheld}
                            />
                            <label className="flex items-center gap-3 rounded-xl border border-dashed border-input bg-card p-3 text-sm font-bold">
                                <Checkbox
                                    checked={
                                        form.data.certificate_received_on !==
                                        null
                                    }
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'certificate_received_on',
                                            checked === true
                                                ? todayIso()
                                                : null,
                                        )
                                    }
                                />
                                I already have their 2307
                                <span className="font-normal text-muted-foreground">
                                    (or add it later)
                                </span>
                            </label>
                        </div>
                    )}
                </div>
            )}

            <PreviewBox
                type={type}
                regime={context.regime}
                preview={preview}
                loading={loading}
                amount={Number(form.data.amount || 0)}
                withheld={
                    withheld && asksWithholding
                        ? Number(form.data.tax_withheld || 0)
                        : 0
                }
                date={form.data.occurred_on}
            />

            {preview?.filed && (
                <p className="flex items-start gap-2 rounded-xl bg-warning-soft p-3 text-[13px] text-warning">
                    <AlertTriangle
                        className="mt-0.5 size-4 shrink-0"
                        aria-hidden
                    />
                    Q{preview.quarter} is already filed. Changing a filed
                    quarter needs an amended return, which comes in a later
                    update.
                </p>
            )}
            <div className="flex gap-2">
                {editing && (
                    <Button
                        type="button"
                        variant="outline"
                        className="h-12 rounded-xl"
                        onClick={() => setConfirm('delete')}
                    >
                        Delete
                    </Button>
                )}
                <Button
                    type="submit"
                    disabled={form.processing}
                    className="h-12 flex-1 rounded-xl text-base font-bold"
                >
                    {form.processing
                        ? 'Saving…'
                        : editing
                          ? 'Save changes'
                          : type === 'invoice'
                            ? 'Save invoice'
                            : type === 'expense'
                              ? 'Save expense'
                              : 'Save'}
                </Button>
            </div>

            <Dialog
                open={confirm !== null}
                onOpenChange={(open) => !open && setConfirm(null)}
            >
                <DialogContent className="max-w-[480px] rounded-[22px]">
                    {confirm === 'duplicate' && preview?.duplicate && (
                        <>
                            <DialogTitle>Already recorded?</DialogTitle>
                            <DialogDescription>
                                You saved {peso(preview.duplicate.amount)} from{' '}
                                {preview.duplicate.counterparty} on{' '}
                                {formatDate(preview.duplicate.occurred_on)}.
                                Saving again would count it twice.
                            </DialogDescription>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    onClick={() => setConfirm(null)}
                                >
                                    Don't save
                                </Button>
                                <Button onClick={() => save(true)}>
                                    It's a second payment
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                    {confirm === 'unusual' && preview?.unusual_multiple && (
                        <>
                            <DialogTitle>
                                {peso(Number(form.data.amount))} is{' '}
                                {preview.unusual_multiple} times your usual
                            </DialogTitle>
                            <DialogDescription>
                                Just checking for an extra zero. This one entry
                                would add {peso(preview.tax_effect)} to your Q
                                {preview.quarter} tax.
                            </DialogDescription>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    onClick={() => setConfirm(null)}
                                >
                                    Fix amount
                                </Button>
                                <Button onClick={() => save(true)}>
                                    Yes, it's right
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                    {confirm === 'delete' && editing && (
                        <>
                            <DialogTitle>Delete this entry?</DialogTitle>
                            <DialogDescription>
                                {peso(editing.amount)} · {editing.counterparty}{' '}
                                · {formatDate(editing.occurred_on)}. Your tax
                                will be recomputed without it.
                            </DialogDescription>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    onClick={() => setConfirm(null)}
                                >
                                    Keep it
                                </Button>
                                <Button variant="destructive" onClick={remove}>
                                    Delete
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </form>
    );
}
