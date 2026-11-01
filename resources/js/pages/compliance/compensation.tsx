import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, FileText } from 'lucide-react';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { peso } from '@/lib/format';
import { home } from '@/routes';
import { file as storedFile, store } from '@/routes/compensation';

type Figures = { taxable: number; withheld: number; tax: number };

type Props = {
    year: number;
    employer: string | null;
    certificate: {
        employer_name: string;
        employer_tin: string | null;
        gross_compensation: number;
        non_taxable_compensation: number;
        tax_withheld: number;
        original_filename: string | null;
    } | null;
    estimate: Figures | null;
    actual: Figures | null;
};

type Form = {
    employer_name: string;
    employer_tin: string;
    gross_compensation: string;
    non_taxable_compensation: string;
    tax_withheld: string;
    file: File | null;
};

export default function Compensation({
    year,
    employer,
    certificate,
    estimate,
    actual,
}: Props) {
    const form = useForm<Form>({
        employer_name: certificate?.employer_name ?? employer ?? '',
        employer_tin: certificate?.employer_tin ?? '',
        gross_compensation: certificate
            ? String(certificate.gross_compensation)
            : '',
        non_taxable_compensation: certificate
            ? String(certificate.non_taxable_compensation)
            : '',
        tax_withheld: certificate ? String(certificate.tax_withheld) : '',
        file: null,
    });
    const difference = actual ? Math.round(actual.tax - actual.withheld) : 0;
    const money = (
        name:
            | 'gross_compensation'
            | 'non_taxable_compensation'
            | 'tax_withheld',
        label: string,
        hint?: string,
    ) => (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={name} className="font-bold">
                {label}
            </Label>
            <MoneyInput
                id={name}
                value={form.data[name]}
                onValueChange={(value) => form.setData(name, value)}
            />
            {hint && !form.errors[name] && (
                <p className="text-[13px] text-muted-foreground">{hint}</p>
            )}
            <FieldError message={form.errors[name]} />
        </div>
    );

    return (
        <>
            <Head title={`2316 · ${year}`} />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={home().url}
                    aria-label="Back to Home"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Your 2316 from work
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {certificate?.employer_name ??
                            employer ??
                            'Your employer'}{' '}
                        · tax year {year}
                    </p>
                </div>
            </header>

            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(store(year), {
                        preserveScroll: true,
                        onSuccess: () => form.setData('file', null),
                    });
                }}
                className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]"
            >
                <div className="flex flex-col gap-5">
                    <SectionCard className="flex items-center gap-4 p-4">
                        <label className="flex size-[90px] shrink-0 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl bg-[#E3E7EC] text-xs text-slate-600 hover:bg-slate-300/60">
                            <input
                                type="file"
                                accept="application/pdf,image/jpeg,image/png,image/webp"
                                className="sr-only"
                                onChange={(event) =>
                                    form.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <FileText className="size-6" aria-hidden />
                            {form.data.file || certificate?.original_filename
                                ? 'Replace'
                                : 'Add photo'}
                        </label>
                        <div className="min-w-0">
                            <p className="text-[15px] font-bold">
                                BIR Form 2316
                            </p>
                            <p className="truncate text-sm text-muted-foreground">
                                {form.data.file ? (
                                    form.data.file.name
                                ) : certificate?.original_filename ? (
                                    <a
                                        href={storedFile(year).url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {certificate.original_filename}
                                    </a>
                                ) : (
                                    'Photo or PDF, kept on this computer (optional)'
                                )}
                            </p>
                            <FieldError message={form.errors.file} />
                        </div>
                    </SectionCard>

                    <SectionCard title="From the 2316">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="employer_name"
                                    className="font-bold"
                                >
                                    Employer
                                </Label>
                                <Input
                                    id="employer_name"
                                    value={form.data.employer_name}
                                    onChange={(event) =>
                                        form.setData(
                                            'employer_name',
                                            event.target.value,
                                        )
                                    }
                                    className="h-11 rounded-xl"
                                />
                                <FieldError
                                    message={form.errors.employer_name}
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="employer_tin"
                                    className="font-bold"
                                >
                                    Employer's TIN{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <Input
                                    id="employer_tin"
                                    placeholder="000-000-000-000"
                                    value={form.data.employer_tin}
                                    onChange={(event) =>
                                        form.setData(
                                            'employer_tin',
                                            event.target.value,
                                        )
                                    }
                                    className="h-11 rounded-xl"
                                />
                                <FieldError
                                    message={form.errors.employer_tin}
                                />
                            </div>
                            {money(
                                'gross_compensation',
                                'Gross compensation',
                                'Total salary and benefits for the year.',
                            )}
                            {money(
                                'non_taxable_compensation',
                                'Non-taxable / exempt',
                                '13th-month pay up to ₱90,000, contributions, de minimis.',
                            )}
                            {money('tax_withheld', 'Tax withheld by employer')}
                        </div>
                    </SectionCard>

                    {(estimate || actual) && (
                        <SectionCard title="Your estimate vs the 2316">
                            <table className="w-full text-[15px] tabular-nums">
                                <thead>
                                    <tr className="border-b text-xs font-bold text-slate-600">
                                        <th className="py-2 text-left" />
                                        <th
                                            scope="col"
                                            className="py-2 text-right"
                                        >
                                            Estimate
                                        </th>
                                        <th
                                            scope="col"
                                            className="py-2 text-right"
                                        >
                                            2316
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(
                                        [
                                            ['Taxable compensation', 'taxable'],
                                            [
                                                'Tax withheld by employer',
                                                'withheld',
                                            ],
                                            ['Tax due on salary', 'tax'],
                                        ] as const
                                    ).map(([label, key]) => (
                                        <tr
                                            key={key}
                                            className="border-b border-divider last:border-0"
                                        >
                                            <td
                                                className={
                                                    key === 'tax'
                                                        ? 'py-3 font-bold'
                                                        : 'py-3'
                                                }
                                            >
                                                {label}
                                            </td>
                                            <td className="py-3 text-right font-bold">
                                                {estimate
                                                    ? peso(estimate[key])
                                                    : '—'}
                                            </td>
                                            <td className="py-3 text-right font-bold">
                                                {actual
                                                    ? peso(actual[key])
                                                    : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </SectionCard>
                    )}
                </div>

                <div className="flex flex-col gap-4">
                    {actual === null ? (
                        <Note
                            title={`Your employer gives it by Jan 31, ${year + 1}`}
                        >
                            Until it's in, your annual return uses your monthly
                            salary for twelve months, with the tax on it assumed
                            withheld.
                        </Note>
                    ) : difference === 0 ? (
                        <Note
                            tone="success"
                            title="Your employer withheld the right amount"
                        >
                            Tax on {peso(actual.taxable)} is {peso(actual.tax)},
                            and that's what was withheld. Nothing extra to pay
                            on your salary.
                        </Note>
                    ) : difference > 0 ? (
                        <Note
                            tone="warning"
                            title={`${peso(difference)} more on your salary`}
                        >
                            Tax on {peso(actual.taxable)} is {peso(actual.tax)},
                            but {peso(actual.withheld)} was withheld. The
                            difference is added to your annual return.
                        </Note>
                    ) : (
                        <Note title={`${peso(-difference)} withheld too much`}>
                            Tax on {peso(actual.taxable)} is {peso(actual.tax)},
                            but {peso(actual.withheld)} was withheld. Your
                            annual return credits the difference.
                        </Note>
                    )}

                    {actual &&
                        estimate &&
                        actual.taxable !== estimate.taxable && (
                            <Note title="Why it changed">
                                The 2316 shows{' '}
                                {peso(
                                    Math.abs(actual.taxable - estimate.taxable),
                                )}{' '}
                                {actual.taxable > estimate.taxable
                                    ? 'more'
                                    : 'less'}{' '}
                                taxable salary than your estimate. Bonuses above
                                the ₱90,000 tax-free limit for 13th-month pay
                                and other benefits are taxable.
                            </Note>
                        )}

                    <Note title="Used in your annual return">
                        We use the 2316 figures, not the estimate, and attach it
                        when you file.
                    </Note>

                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="h-12 rounded-xl text-[15px] font-bold"
                    >
                        {certificate ? 'Update 2316' : 'Save 2316'}
                    </Button>
                </div>
            </form>
        </>
    );
}
