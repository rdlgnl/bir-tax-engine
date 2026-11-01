import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { DownloadCard } from '@/components/download-card';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, peso, todayIso } from '@/lib/format';
import {
    destroy,
    filed as markFiled,
    form as formPage,
    index,
    pdf,
    store,
    update,
} from '@/routes/loans';
import type { Loan } from '@/types';

type Props = { loan: Loan | null };

type LoanForm = {
    signed_on: string;
    amount: string;
    lender: string;
    lender_tin: string;
    lender_kind: 'bank' | 'person';
    term_months: string;
};

export default function LoanEdit({ loan }: Props) {
    const form = useForm<LoanForm>({
        signed_on: loan?.signed_on ?? todayIso(),
        amount: loan ? String(loan.amount) : '',
        lender: loan?.lender ?? '',
        lender_tin: loan?.lender_tin ?? '',
        lender_kind: loan?.lender_kind ?? 'person',
        term_months: loan ? String(loan.term_months) : '12',
    });
    const dst = loan?.dst;
    const bank = form.data.lender_kind === 'bank';
    const month = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(
        new Date(`${form.data.signed_on || todayIso()}T00:00:00`),
    );

    function save() {
        form.submit(loan ? update(loan.id) : store(), { preserveScroll: true });
    }

    return (
        <>
            <Head
                title={loan ? `Loan from ${loan.lender}` : 'A loan you signed'}
            />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={index().url}
                    aria-label="Back to loans"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        A loan you signed
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Documentary stamp tax (DST) · Form 2000
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <form
                    noValidate
                    onSubmit={(event) => {
                        event.preventDefault();
                        save();
                    }}
                    className="flex flex-col gap-4"
                >
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="amount" className="font-bold">
                            Loan amount
                        </Label>
                        <MoneyInput
                            id="amount"
                            size="large"
                            value={form.data.amount}
                            onValueChange={(value) =>
                                form.setData('amount', value)
                            }
                            autoFocus={!loan}
                        />
                        <FieldError message={form.errors.amount} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_200px]">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="lender" className="font-bold">
                                Lent by
                            </Label>
                            <Input
                                id="lender"
                                value={form.data.lender}
                                onChange={(event) =>
                                    form.setData('lender', event.target.value)
                                }
                                className="h-12 rounded-xl"
                            />
                            <FieldError message={form.errors.lender} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="lender_tin" className="font-bold">
                                Their TIN{' '}
                                <span className="font-normal text-muted-foreground">
                                    (optional)
                                </span>
                            </Label>
                            <Input
                                id="lender_tin"
                                placeholder="000-000-000-000"
                                value={form.data.lender_tin}
                                onChange={(event) =>
                                    form.setData(
                                        'lender_tin',
                                        event.target.value,
                                    )
                                }
                                className="h-12 rounded-xl"
                            />
                            <FieldError message={form.errors.lender_tin} />
                        </div>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <span className="text-sm font-bold">Who lent it?</span>
                        <ChoiceChips<'bank' | 'person'>
                            label="Who lent it?"
                            variant="segmented"
                            value={form.data.lender_kind}
                            onChange={(value) =>
                                form.setData('lender_kind', value)
                            }
                            options={[
                                { value: 'bank', label: 'A bank' },
                                {
                                    value: 'person',
                                    label: 'A person or company',
                                },
                            ]}
                            className="max-w-md"
                        />
                        <p className="text-[13px] text-muted-foreground">
                            Banks usually file the DST themselves and deduct it
                            from what you receive.
                        </p>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="signed_on" className="font-bold">
                                Signed on
                            </Label>
                            <Input
                                id="signed_on"
                                type="date"
                                max={todayIso()}
                                value={form.data.signed_on}
                                onChange={(event) =>
                                    form.setData(
                                        'signed_on',
                                        event.target.value,
                                    )
                                }
                                className="h-12 rounded-xl"
                            />
                            <FieldError message={form.errors.signed_on} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="term_months" className="font-bold">
                                Term (months)
                            </Label>
                            <Input
                                id="term_months"
                                type="number"
                                min={1}
                                value={form.data.term_months}
                                onChange={(event) =>
                                    form.setData(
                                        'term_months',
                                        event.target.value,
                                    )
                                }
                                className="h-12 rounded-xl"
                            />
                            <FieldError message={form.errors.term_months} />
                        </div>
                    </div>

                    {dst && loan && (
                        <SectionCard title="DST on this loan">
                            <dl className="text-[15px] tabular-nums">
                                <Row
                                    label="Signed"
                                    value={formatDate(loan.signed_on, 'long')}
                                />
                                <Row
                                    label="Term"
                                    value={`${loan.term_months} months`}
                                />
                                <Row label="Rate" value="₱1.50 per ₱200" />
                                {dst.penalties && (
                                    <Row
                                        label="Late: surcharge and interest"
                                        value={peso(dst.penalties.total)}
                                    />
                                )}
                                <div className="flex items-center justify-between pt-3">
                                    <dt className="font-bold">
                                        {dst.status === 'bank'
                                            ? 'DST (the bank files it)'
                                            : 'DST to pay'}
                                    </dt>
                                    <dd className="text-2xl font-extrabold">
                                        {peso(dst.total)}
                                    </dd>
                                </div>
                            </dl>
                        </SectionCard>
                    )}

                    <div className="flex flex-wrap gap-2.5">
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="h-12 rounded-xl px-6 text-[15px] font-bold"
                        >
                            Save loan
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <Link href={index().url}>Cancel</Link>
                        </Button>
                        {loan && (
                            <Button
                                type="button"
                                variant="ghost"
                                className="h-12 rounded-xl text-destructive"
                                onClick={() => {
                                    if (window.confirm('Delete this loan?')) {
                                        router.delete(destroy(loan.id).url);
                                    }
                                }}
                            >
                                Delete
                            </Button>
                        )}
                    </div>
                </form>

                <div className="flex flex-col gap-4">
                    {bank ? (
                        <Note title="The bank files it">
                            Banks file the DST on their loans and deduct it from
                            what you receive. Nothing for you to file; keep the
                            loan papers.
                        </Note>
                    ) : dst?.status === 'filed' && loan?.filed_on ? (
                        <Note tone="success" title="Form 2000 filed">
                            Marked filed on {formatDate(loan.filed_on, 'long')}.
                        </Note>
                    ) : (
                        <Note
                            tone={
                                dst?.status === 'overdue' ? 'danger' : 'warning'
                            }
                            title={
                                dst
                                    ? `File Form 2000 by ${formatDate(dst.due_date, 'short')}`
                                    : 'File Form 2000 within 5 days after the month'
                            }
                        >
                            DST is due 5 days after the end of the month the
                            loan was signed ({month}). You and the lender are
                            both responsible; agree who files.
                        </Note>
                    )}
                    <Note title="Not your income">
                        A loan isn't a sale. It doesn't change your income tax.
                    </Note>

                    {loan && !bank && (
                        <>
                            <DownloadCard
                                href={pdf(loan.id).url}
                                title="Download filled Form 2000"
                                detail="Official DST return, filled for you."
                            />
                            <Link
                                href={formPage(loan.id).url}
                                className="-mt-1 text-center text-sm font-bold text-primary hover:underline"
                            >
                                Preview the filled form
                            </Link>
                            {dst?.status !== 'filed' && (
                                <MarkFiled loan={loan} />
                            )}
                        </>
                    )}
                </div>
            </div>
        </>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-3 border-b border-divider py-3">
            <dt>{label}</dt>
            <dd className="font-bold">{value}</dd>
        </div>
    );
}

function MarkFiled({ loan }: { loan: Loan }) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        filed_on: todayIso(),
        reference_number: '',
        amount_paid: String(loan.dst.total),
    });

    if (!open) {
        return (
            <Button
                variant="outline"
                onClick={() => setOpen(true)}
                className="h-12 rounded-xl border-2 border-primary text-[15px] font-bold text-primary"
            >
                I've filed it · mark as filed
            </Button>
        );
    }

    return (
        <SectionCard>
            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(markFiled(loan.id), { preserveScroll: true });
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
                    Mark Form 2000 filed
                </Button>
            </form>
        </SectionCard>
    );
}
