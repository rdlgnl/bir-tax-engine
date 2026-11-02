import { Head, Link } from '@inertiajs/react';
import { Check, ChevronLeft, Download } from 'lucide-react';
import { FiguresForm } from '@/components/corporation/figures-form';
import { MarkCorporateFiled } from '@/components/corporation/mark-corporate-filed';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, minusPeso, peso, periodLabel } from '@/lib/format';
import { filed, form as formPage, show } from '@/routes/corporation';
import type {
    CorporateQuarterFigures,
    CorporateQuarterTax,
    Corporation,
} from '@/types';

type Props = {
    corporation: Corporation;
    tax: CorporateQuarterTax;
    figures: CorporateQuarterFigures | null;
    mcitRate: number;
};

const percent = (rate: number) => `${Math.round(rate * 100)}%`;

export default function CorporationQuarter({
    corporation,
    tax,
    figures,
    mcitRate,
}: Props) {
    const t = tax.to_date;
    const osd = corporation.deduction_method === 'osd';
    const earlier =
        tax.quarter === 1
            ? null
            : tax.quarter === 2
              ? 'Paid with Q1'
              : 'Paid with Q1 and Q2';

    return (
        <>
            <Head title={`Q${tax.quarter} corporate income tax`} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={show().url}
                    aria-label="Back to the corporation"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Q{tax.quarter} corporate income tax
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        BIR Form 1702Q · {periodLabel(tax.quarter)} {tax.year},
                        cumulative
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    {!tax.has_figures ? (
                        <FiguresForm
                            year={tax.year}
                            quarter={tax.quarter}
                            figures={figures}
                            osd={osd}
                            startOpen
                        />
                    ) : (
                        <>
                            <SectionCard title="How it adds up">
                                <dl className="divide-y divide-divider text-[15px]">
                                    <Row label="Sales" value={peso(t.sales)} />
                                    <Row
                                        label="Less: cost of services"
                                        value={minusPeso(t.cost_of_sales)}
                                        credit
                                    />
                                    {t.other_income > 0 && (
                                        <Row
                                            label="Add: other income"
                                            value={peso(t.other_income)}
                                        />
                                    )}
                                    <Row
                                        label="Gross income"
                                        value={peso(t.gross_income)}
                                    />
                                    <Row
                                        label={
                                            osd
                                                ? 'Less: optional standard deduction (40%)'
                                                : 'Less: itemized deductions'
                                        }
                                        value={minusPeso(t.deductions)}
                                        credit
                                    />
                                    <Row
                                        label="Taxable income"
                                        value={peso(t.taxable_income)}
                                    />
                                    <Row
                                        label={`Regular tax (${percent(tax.rate)}${tax.small ? ', provisional' : ''})`}
                                        value={peso(tax.regular_tax)}
                                    />
                                </dl>
                                {tax.small && (
                                    <p className="mt-3 text-[13px] text-muted-foreground">
                                        20% while taxable income so far is
                                        within the limit. The annual return
                                        tests the whole year; over the limit,
                                        the year is at 25% and the difference is
                                        paid then.
                                    </p>
                                )}
                            </SectionCard>

                            {tax.mcit_applies && (
                                <SectionCard title="Regular tax vs minimum tax">
                                    <table className="w-full text-[15px]">
                                        <thead>
                                            <tr className="border-b border-divider text-xs font-bold text-slate-600">
                                                <th className="py-2 text-left font-bold">
                                                    <span className="sr-only">
                                                        Tax
                                                    </span>
                                                </th>
                                                <th className="py-2 text-right">
                                                    Amount
                                                </th>
                                                <th className="w-16 py-2 text-right">
                                                    Used
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-divider">
                                            <tr>
                                                <td className="py-2.5">
                                                    Regular ({percent(tax.rate)}{' '}
                                                    of taxable income)
                                                </td>
                                                <td className="py-2.5 text-right font-bold">
                                                    {peso(tax.regular_tax)}
                                                </td>
                                                <td className="py-2.5 text-right">
                                                    {tax.uses === 'regular' && (
                                                        <Check
                                                            className="ml-auto size-4"
                                                            aria-label="Used"
                                                        />
                                                    )}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td className="py-2.5">
                                                    Minimum ({percent(mcitRate)}{' '}
                                                    of gross income)
                                                </td>
                                                <td className="py-2.5 text-right font-bold">
                                                    {peso(tax.mcit)}
                                                </td>
                                                <td className="py-2.5 text-right">
                                                    {tax.uses === 'mcit' && (
                                                        <Check
                                                            className="ml-auto size-4"
                                                            aria-label="Used"
                                                        />
                                                    )}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </SectionCard>
                            )}

                            <SectionCard title="What you still pay">
                                <dl className="divide-y divide-divider text-[15px]">
                                    <Row
                                        label="Tax due"
                                        value={peso(tax.tax_due)}
                                    />
                                    {earlier && (
                                        <Row
                                            label={earlier}
                                            value={minusPeso(
                                                tax.paid_regular +
                                                    tax.paid_mcit,
                                            )}
                                            credit
                                        />
                                    )}
                                    <Row
                                        label="Withheld by clients (2307s)"
                                        value={minusPeso(
                                            tax.withheld_previous +
                                                tax.withheld_this_quarter,
                                        )}
                                        credit
                                    />
                                    <div className="flex items-baseline justify-between gap-3 pt-4 pb-1">
                                        <dt className="font-extrabold">
                                            {tax.payable < 0
                                                ? 'Overpayment'
                                                : 'Tax payable'}
                                        </dt>
                                        <dd className="text-2xl font-extrabold">
                                            {peso(tax.payable)}
                                        </dd>
                                    </div>
                                </dl>
                            </SectionCard>

                            <FiguresForm
                                year={tax.year}
                                quarter={tax.quarter}
                                figures={figures}
                                osd={osd}
                            />
                        </>
                    )}
                </div>

                <div className="flex flex-col gap-4">
                    {tax.has_figures && (
                        <Link
                            href={formPage([tax.year, tax.quarter]).url}
                            className="flex items-center gap-4 rounded-card bg-ink p-5 text-ink-foreground hover:bg-slate-800"
                        >
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary">
                                <Download className="size-5" aria-hidden />
                            </span>
                            <span>
                                <span className="block text-lg font-extrabold">
                                    Download filled 1702Q
                                </span>
                                <span className="text-sm text-slate-300">
                                    Official BIR form, filled from your books.
                                </span>
                            </span>
                        </Link>
                    )}
                    <Note
                        tone={tax.status === 'overdue' ? 'danger' : 'info'}
                        title={`Due ${formatDate(tax.due_date, 'full')}`}
                    >
                        {tax.due_note}
                    </Note>
                    <Note title="Attach">
                        The SAWT, for the 2307s claimed. Most corporations file
                        online (eFPS or eBIRForms).
                    </Note>
                    <Button
                        asChild
                        variant="outline"
                        className="h-12 rounded-xl text-[15px] font-bold"
                    >
                        <Link href={show().url}>Back</Link>
                    </Button>
                    {tax.filed_on ? (
                        <Note tone="success" title={`Q${tax.quarter} filed`}>
                            Marked filed on {formatDate(tax.filed_on, 'long')}
                            {tax.amount_paid !== null &&
                                `, ${peso(tax.amount_paid)} paid`}
                            {tax.reference_number &&
                                ` (reference ${tax.reference_number})`}
                            .
                        </Note>
                    ) : (
                        tax.has_figures &&
                        tax.status !== 'open' && (
                            <MarkCorporateFiled
                                action={filed([tax.year, tax.quarter])}
                                payable={tax.payable}
                                form={`Q${tax.quarter} 1702Q`}
                                label={`Mark Q${tax.quarter} filed`}
                            />
                        )
                    )}
                </div>
            </div>
        </>
    );
}

function Row({
    label,
    value,
    credit = false,
}: {
    label: string;
    value: string;
    credit?: boolean;
}) {
    return (
        <div className="flex justify-between gap-3 py-3">
            <dt>{label}</dt>
            <dd
                className={
                    credit
                        ? 'text-right font-bold text-success'
                        : 'text-right font-bold'
                }
            >
                {value}
            </dd>
        </div>
    );
}
