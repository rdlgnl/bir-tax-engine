import { Head, Link, useForm, useHttp } from '@inertiajs/react';
import {
    AlertTriangle,
    Check,
    ChevronLeft,
    FileText,
    Info,
} from 'lucide-react';
import { useEffect, useId, useMemo, useState } from 'react';
import { FieldError } from '@/components/field-error';
import { MoneyInput } from '@/components/money-input';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useServerPreview } from '@/hooks/use-server-preview';
import { formatDate, peso, plural, quarterMonths } from '@/lib/format';
import { cn } from '@/lib/utils';
import { match, read, store } from '@/routes/certificates';
import { show as complianceShow } from '@/routes/compliance';

type Props = {
    clients: string[];
    quarters: { year: number; quarter: number }[];
    prefill: {
        payor_name: string;
        year: number;
        quarter: number;
        amount_paid: number;
        tax_withheld: number;
    } | null;
};

type CertificateForm = {
    payor_name: string;
    payor_tin: string;
    year: number;
    quarter: number;
    amount_paid: string;
    tax_withheld: string;
    atc_code: string;
    file: File | null;
};

type ReadField<T> = { value: T | null; sure: boolean };

/** App\Compliance\Services\CertificateReader */
type Reading = {
    readable: boolean;
    fields: {
        payor_name: ReadField<string>;
        payor_tin: ReadField<string>;
        year: ReadField<number>;
        quarter: ReadField<number>;
        amount_paid: ReadField<number>;
        tax_withheld: ReadField<number>;
        atc_code: ReadField<string>;
    };
};

const nothing = { value: null, sure: false };

const UNREADABLE: Reading = {
    readable: false,
    fields: {
        payor_name: nothing,
        payor_tin: nothing,
        year: nothing,
        quarter: nothing,
        amount_paid: nothing,
        tax_withheld: nothing,
        atc_code: nothing,
    },
};

/** The fields a reading fills, the period counting once. */
type ReadKey =
    | 'payor_name'
    | 'payor_tin'
    | 'period'
    | 'amount_paid'
    | 'tax_withheld'
    | 'atc_code';

type Match = {
    status: 'exact' | 'partial' | 'none';
    withheld: number;
    payments: {
        id: number;
        counterparty: string;
        occurred_on: string;
        amount: number;
        tax_withheld: number;
    }[];
};

export default function CertificateCreate({
    clients,
    quarters,
    prefill,
}: Props) {
    const clientListId = useId();
    const [preview, setPreview] = useState<string | null>(null);
    const [dragging, setDragging] = useState(false);
    const form = useForm<CertificateForm>({
        payor_name: prefill?.payor_name ?? '',
        payor_tin: '',
        year: prefill?.year ?? quarters[0].year,
        quarter: prefill?.quarter ?? quarters[0].quarter,
        amount_paid: prefill ? String(prefill.amount_paid) : '',
        tax_withheld: prefill ? String(prefill.tax_withheld) : '',
        atc_code: '',
        file: null,
    });
    const { year, quarter } = form.data;
    const reader = useHttp<{ file: File | null }, Reading>({ file: null });
    const [reading, setReading] = useState<Reading | null>(null);
    const [toCheck, setToCheck] = useState<ReadKey[]>([]);
    const found = reading?.readable
        ? (
              [
                  'payor_name',
                  'payor_tin',
                  'year',
                  'amount_paid',
                  'tax_withheld',
                  'atc_code',
              ] as const
          ).filter((key) => reading.fields[key].value !== null).length
        : 0;
    // A certificate read for a quarter outside the list still has to show.
    const periods = useMemo(
        () =>
            quarters.some((q) => q.year === year && q.quarter === quarter)
                ? quarters
                : [{ year, quarter }, ...quarters],
        [quarters, year, quarter],
    );

    const { response: result, loading } = useServerPreview<
        {
            payor_name: string;
            year: number;
            quarter: number;
            tax_withheld: string;
        },
        Match
    >(
        match,
        {
            payor_name: form.data.payor_name,
            year,
            quarter,
            tax_withheld: form.data.tax_withheld || '0',
        },
        form.data.payor_name.trim().length > 1,
    );

    // Show the file without sending it anywhere; it's uploaded only on save.
    useEffect(() => {
        if (!form.data.file) {
            setPreview(null);

            return;
        }

        const url = URL.createObjectURL(form.data.file);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [form.data.file]);

    /**
     * Reads the file's details on this computer and fills the form; anything
     * the reader isn't sure of is marked to check.
     */
    function chooseFile(file: File | null) {
        form.setData('file', file);
        setReading(null);
        setToCheck([]);

        if (!file) {
            return;
        }

        reader.transform(() => ({ file }));
        reader
            .submit(read())
            .then((result) => {
                setReading(result);

                if (!result.readable) {
                    return;
                }

                const f = result.fields;
                const period =
                    f.year.value !== null && f.quarter.value !== null;
                form.setData((data) => ({
                    ...data,
                    payor_name: f.payor_name.value ?? data.payor_name,
                    payor_tin: f.payor_tin.value ?? data.payor_tin,
                    year: period ? (f.year.value as number) : data.year,
                    quarter: period
                        ? (f.quarter.value as number)
                        : data.quarter,
                    amount_paid:
                        f.amount_paid.value !== null
                            ? String(f.amount_paid.value)
                            : data.amount_paid,
                    tax_withheld:
                        f.tax_withheld.value !== null
                            ? String(f.tax_withheld.value)
                            : data.tax_withheld,
                    atc_code: f.atc_code.value ?? data.atc_code,
                }));
                setToCheck(
                    (
                        [
                            ['payor_name', f.payor_name],
                            ['payor_tin', f.payor_tin],
                            ['period', f.quarter],
                            ['amount_paid', f.amount_paid],
                            ['tax_withheld', f.tax_withheld],
                            ['atc_code', f.atc_code],
                        ] as [ReadKey, ReadField<unknown>][]
                    )
                        .filter(
                            ([, field]) => field.value !== null && !field.sure,
                        )
                        .map(([key]) => key),
                );
            })
            // A file that can't be read is typed in, like a photo.
            .catch(() => setReading(UNREADABLE));
    }

    /** Once the taxpayer changes a field, it's theirs: no longer to check. */
    function checked(key: ReadKey) {
        setToCheck((keys) => keys.filter((k) => k !== key));
    }

    function submit() {
        form.submit(store());
    }

    const file = form.data.file;
    const isImage = file?.type.startsWith('image/');

    return (
        <>
            <Head title="Add a 2307" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={complianceShow().url}
                    aria-label="Back to Compliance"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Tax certificate (2307)
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Proof a client paid tax for you · kept on this computer
                    </p>
                </div>
            </header>

            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    submit();
                }}
                className="grid items-start gap-5 lg:grid-cols-2"
            >
                <SectionCard className="flex flex-col gap-3 p-5">
                    <label
                        onDragOver={(event) => {
                            event.preventDefault();
                            setDragging(true);
                        }}
                        onDragLeave={() => setDragging(false)}
                        onDrop={(event) => {
                            event.preventDefault();
                            setDragging(false);
                            chooseFile(event.dataTransfer.files[0] ?? null);
                        }}
                        className={cn(
                            'flex min-h-[420px] cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl bg-[#E3E7EC] text-sm text-slate-600 lg:min-h-[560px]',
                            dragging && 'ring-2 ring-primary',
                        )}
                    >
                        <input
                            type="file"
                            accept="application/pdf,image/jpeg,image/png,image/webp"
                            className="sr-only"
                            onChange={(event) =>
                                chooseFile(event.target.files?.[0] ?? null)
                            }
                        />
                        {preview && isImage ? (
                            <img
                                src={preview}
                                alt={`Preview of ${file?.name}`}
                                className="max-h-[560px] w-full object-contain"
                            />
                        ) : preview ? (
                            <object
                                data={preview}
                                type="application/pdf"
                                className="h-[560px] w-full"
                                aria-label={`Preview of ${file?.name}`}
                            >
                                <FileText className="size-8" aria-hidden />
                                {file?.name}
                            </object>
                        ) : (
                            <>
                                <FileText className="size-8" aria-hidden />
                                <span className="font-bold">
                                    Drop the 2307 here, or click to choose
                                </span>
                                <span>
                                    PDF or photo, up to 10 MB (optional)
                                </span>
                            </>
                        )}
                    </label>
                    <FieldError message={form.errors.file} />
                    <div className="flex items-center justify-between gap-3">
                        <span
                            role="status"
                            className="truncate text-sm text-muted-foreground"
                        >
                            {!file
                                ? 'No file attached'
                                : reader.processing
                                  ? `Reading ${file.name}…`
                                  : reading?.readable
                                    ? `${plural(found, 'field')} found · ${toCheck.length} to check`
                                    : reading
                                      ? 'No text to read: type the details'
                                      : file.name}
                        </span>
                        {file && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => chooseFile(null)}
                            >
                                Remove file
                            </Button>
                        )}
                    </div>
                    <p className="text-[13px] text-muted-foreground">
                        {reading && !reading.readable
                            ? "This looks like a scan or a photo, which can't be read yet. Type the details from it on the right."
                            : 'Drag a PDF or photo here. Files are read on this computer and never uploaded.'}
                    </p>
                </SectionCard>

                <div className="flex flex-col gap-4">
                    <SectionCard className="flex flex-col gap-4">
                        <Field
                            label="Client (payor)"
                            htmlFor="payor_name"
                            error={form.errors.payor_name}
                            check={toCheck.includes('payor_name')}
                        >
                            <Input
                                id="payor_name"
                                list={clientListId}
                                autoComplete="off"
                                value={form.data.payor_name}
                                onChange={(event) => {
                                    form.setData(
                                        'payor_name',
                                        event.target.value,
                                    );
                                    checked('payor_name');
                                }}
                                className="h-11 rounded-xl"
                            />
                            <datalist id={clientListId}>
                                {clients.map((client) => (
                                    <option key={client} value={client} />
                                ))}
                            </datalist>
                        </Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="Client TIN"
                                htmlFor="payor_tin"
                                error={form.errors.payor_tin}
                                optional
                                check={toCheck.includes('payor_tin')}
                            >
                                <Input
                                    id="payor_tin"
                                    inputMode="numeric"
                                    placeholder="008-123-456-000"
                                    value={form.data.payor_tin}
                                    onChange={(event) => {
                                        form.setData(
                                            'payor_tin',
                                            event.target.value,
                                        );
                                        checked('payor_tin');
                                    }}
                                    className="h-11 rounded-xl"
                                />
                            </Field>
                            <Field
                                label="Period"
                                htmlFor="period"
                                error={form.errors.quarter ?? form.errors.year}
                                check={toCheck.includes('period')}
                            >
                                <Select
                                    value={`${year}-${quarter}`}
                                    onValueChange={(value) => {
                                        const [y, q] = value
                                            .split('-')
                                            .map(Number);
                                        form.setData({
                                            ...form.data,
                                            year: y,
                                            quarter: q,
                                        });
                                        checked('period');
                                    }}
                                >
                                    <SelectTrigger
                                        id="period"
                                        className="!h-11 w-full rounded-xl"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {periods.map((q) => (
                                            <SelectItem
                                                key={`${q.year}-${q.quarter}`}
                                                value={`${q.year}-${q.quarter}`}
                                            >
                                                {quarterMonths(q.quarter)}{' '}
                                                {q.year} (Q{q.quarter})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field
                                label="Amount paid"
                                htmlFor="amount_paid"
                                error={form.errors.amount_paid}
                                check={toCheck.includes('amount_paid')}
                            >
                                <MoneyInput
                                    id="amount_paid"
                                    value={form.data.amount_paid}
                                    onValueChange={(value) => {
                                        form.setData('amount_paid', value);
                                        checked('amount_paid');
                                    }}
                                />
                            </Field>
                            <Field
                                label="Tax withheld"
                                htmlFor="tax_withheld"
                                error={form.errors.tax_withheld}
                                check={toCheck.includes('tax_withheld')}
                            >
                                <MoneyInput
                                    id="tax_withheld"
                                    value={form.data.tax_withheld}
                                    onValueChange={(value) => {
                                        form.setData('tax_withheld', value);
                                        checked('tax_withheld');
                                    }}
                                />
                            </Field>
                        </div>
                        <Field
                            label="Tax code (ATC)"
                            htmlFor="atc_code"
                            error={form.errors.atc_code}
                            optional
                            check={toCheck.includes('atc_code')}
                        >
                            <Input
                                id="atc_code"
                                placeholder="WI010"
                                value={form.data.atc_code}
                                onChange={(event) => {
                                    form.setData(
                                        'atc_code',
                                        event.target.value.toUpperCase(),
                                    );
                                    checked('atc_code');
                                }}
                                className="h-11 max-w-48 rounded-xl"
                            />
                            <p className="text-[13px] text-muted-foreground">
                                Copy it exactly as printed. You won't need to
                                understand it.
                            </p>
                        </Field>
                    </SectionCard>

                    {result && (
                        <MatchBanner
                            result={result}
                            client={form.data.payor_name}
                            quarter={quarter}
                            claimed={Number(form.data.tax_withheld || 0)}
                            loading={loading}
                        />
                    )}

                    <div className="flex justify-end gap-2.5">
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl px-5 font-bold"
                        >
                            <Link href={complianceShow().url}>Cancel</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="h-12 rounded-xl px-6 text-[15px] font-bold"
                        >
                            {form.processing ? 'Saving…' : 'Save certificate'}
                        </Button>
                    </div>
                </div>
            </form>
        </>
    );
}

function Field({
    label,
    htmlFor,
    error,
    optional,
    check = false,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    optional?: boolean;
    /** Read from the file, but not for sure. */
    check?: boolean;
    children: React.ReactNode;
}) {
    return (
        <div
            className={cn(
                'flex flex-col gap-1.5',
                check &&
                    '[&_button]:border-warning-border [&_input]:border-warning-border [&_input]:ring-2 [&_input]:ring-warning-soft',
            )}
        >
            <Label htmlFor={htmlFor} className="text-sm font-bold">
                {label}{' '}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                )}
            </Label>
            {check && (
                <span className="flex items-center gap-1.5 text-[13px] font-bold text-warning">
                    <AlertTriangle className="size-3.5" aria-hidden />
                    Couldn't confirm this one. Please check it against the form.
                </span>
            )}
            {children}
            <FieldError message={error} />
        </div>
    );
}

function MatchBanner({
    result,
    client,
    quarter,
    claimed,
    loading,
}: {
    result: Match;
    client: string;
    quarter: number;
    claimed: number;
    loading: boolean;
}) {
    const one = result.payments.length === 1 ? result.payments[0] : null;
    const tone =
        result.status === 'exact'
            ? 'bg-success-soft text-success'
            : result.status === 'partial'
              ? 'bg-warning-soft text-warning'
              : 'bg-muted text-slate-700';
    const Icon =
        result.status === 'exact'
            ? Check
            : result.status === 'partial'
              ? AlertTriangle
              : Info;

    let message: string;

    if (result.status === 'exact') {
        message = one
            ? `Matches your ${formatDate(one.occurred_on)} payment from ${one.counterparty} (${peso(one.amount)}, ${peso(one.tax_withheld)} withheld). Saving confirms the credit.`
            : `Matches ${plural(result.payments.length, 'payment')} from ${client} in Q${quarter} (${peso(result.withheld)} withheld in total). Saving confirms the credit.`;
    } else if (result.status === 'partial') {
        message = `Your ${plural(result.payments.length, 'payment')} from ${client} in Q${quarter} show ${peso(result.withheld)} withheld, but this 2307 says ${peso(claimed)}. You can save it, but the credit stays unconfirmed until they match.`;
    } else {
        message = `No payment from ${client} in Q${quarter} has tax withheld yet. You can still save the certificate.`;
    }

    return (
        <p
            role="status"
            aria-busy={loading}
            className={cn(
                'flex items-start gap-2.5 rounded-2xl px-4 py-3.5 text-[15px] transition-opacity',
                tone,
                loading && 'opacity-60',
            )}
        >
            <Icon className="mt-0.5 size-5 shrink-0" aria-hidden />
            {message}
        </p>
    );
}
