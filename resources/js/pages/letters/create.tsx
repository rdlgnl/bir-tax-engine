import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ChevronLeft, FileText } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { todayIso } from '@/lib/format';
import { data } from '@/routes';
import { index, read, store } from '@/routes/letters';

type Reading = {
    is_bir_letter: boolean;
    kind: string;
    dated_on: string | null;
    office: string | null;
    form: string | null;
    year: number | null;
    quarter: number | null;
    month: number | null;
    reply_by: string | null;
    summary: string;
};

type Props = {
    kinds: { value: string; label: string }[];
    forms: string[];
    assistant: { ready: boolean; provider: string };
    reading: Reading | null;
};

type LetterForm = {
    kind: string;
    dated_on: string;
    office: string;
    form: string;
    year: string;
    quarter: string;
    month: string;
    reply_by: string;
    notes: string;
    file: File | null;
};

const MONTHLY = ['0619-E', '1601-C', '2000'];
const ANNUAL = ['1701', '1701A', '1701-MS', '1604-E', '1604-C'];

export default function LetterCreate({
    kinds,
    forms,
    assistant,
    reading,
}: Props) {
    const form = useForm<LetterForm>({
        kind: 'unfiled',
        dated_on: todayIso(),
        office: '',
        form: '',
        year: String(new Date().getFullYear()),
        quarter: '',
        month: '',
        reply_by: '',
        notes: '',
        file: null,
    });
    const { errors } = usePage().props;
    const readError = (errors as Record<string, string | undefined>).file;
    const [consent, setConsent] = useState(false);
    const [readingNow, setReadingNow] = useState(false);
    const [filledIn, setFilledIn] = useState<Reading | null>(null);
    const applied = useRef<Reading | null>(null);

    // What the assistant read fills in the form once, for the taxpayer to check.
    useEffect(() => {
        if (reading === null || applied.current === reading) {
            return;
        }

        applied.current = reading;
        setFilledIn(reading);
        form.setData((current) => ({
            ...current,
            kind: reading.kind,
            dated_on: reading.dated_on ?? current.dated_on,
            office: reading.office ?? current.office,
            form: reading.form ?? '',
            year: reading.year !== null ? String(reading.year) : current.year,
            quarter: reading.quarter !== null ? String(reading.quarter) : '',
            month: reading.month !== null ? String(reading.month) : '',
            reply_by: reading.reply_by ?? '',
            notes:
                current.notes.trim() === ''
                    ? reading.summary
                    : `${current.notes}\n\n${reading.summary}`,
        }));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [reading]);

    const readIt = () => {
        if (form.data.file === null) {
            return;
        }

        router.post(
            read().url,
            { file: form.data.file, consent },
            {
                forceFormData: true,
                preserveState: true,
                preserveScroll: true,
                onStart: () => setReadingNow(true),
                onFinish: () => setReadingNow(false),
            },
        );
    };

    const namesReturn = ['unfiled', 'unpaid'].includes(form.data.kind);
    const namesPeriod = namesReturn || form.data.kind === 'mismatch';
    const monthly = MONTHLY.includes(form.data.form);
    const annual = ANNUAL.includes(form.data.form);
    const field = (
        name: 'dated_on' | 'office' | 'year' | 'reply_by',
        label: string,
        type = 'text',
    ) => (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={name} className="font-bold">
                {label}
            </Label>
            <Input
                id={name}
                type={type}
                value={form.data[name]}
                onChange={(event) => form.setData(name, event.target.value)}
                className="h-11 rounded-xl"
            />
            <FieldError message={form.errors[name]} />
        </div>
    );

    return (
        <>
            <Head title="A letter from the BIR" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={index().url}
                    aria-label="Back to letters"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        A letter from the BIR
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Kept on this device · sent nowhere unless you ask the
                        assistant to read it
                    </p>
                </div>
            </header>

            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(store());
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
                                onChange={(event) => {
                                    form.setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    );
                                    setConsent(false);
                                }}
                            />
                            <FileText className="size-6" aria-hidden />
                            {form.data.file ? 'Replace' : 'Add photo'}
                        </label>
                        <div className="min-w-0">
                            <p className="text-[15px] font-bold">The letter</p>
                            <p className="truncate text-sm text-muted-foreground">
                                {form.data.file?.name ??
                                    'A photo or PDF, kept with your documents (optional)'}
                            </p>
                            <FieldError
                                message={form.errors.file ?? readError}
                            />
                        </div>
                    </SectionCard>

                    {form.data.file !== null &&
                        (assistant.ready ? (
                            <SectionCard className="flex flex-col gap-3 p-4">
                                <label className="flex items-start gap-2.5 text-sm">
                                    <Checkbox
                                        className="mt-0.5"
                                        checked={consent}
                                        onCheckedChange={(checked) =>
                                            setConsent(checked === true)
                                        }
                                    />
                                    <span>
                                        Send this letter to {assistant.provider}{' '}
                                        to read it. It shows your name, TIN and
                                        address; nothing else from the app goes
                                        with it.
                                    </span>
                                </label>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={!consent || readingNow}
                                    onClick={readIt}
                                    className="self-start rounded-xl font-bold"
                                >
                                    {readingNow
                                        ? 'Reading the letter…'
                                        : 'Read it with the assistant'}
                                </Button>
                            </SectionCard>
                        ) : (
                            <p className="-mt-2 text-sm text-muted-foreground">
                                The assistant can read the letter and fill this
                                in for you.{' '}
                                <Link
                                    href={data().url}
                                    className="font-bold text-primary hover:underline"
                                >
                                    Turn it on
                                </Link>
                            </p>
                        ))}

                    {filledIn !== null && (
                        <Note
                            tone={filledIn.is_bir_letter ? 'info' : 'warning'}
                            title={
                                filledIn.is_bir_letter
                                    ? 'The assistant filled this in'
                                    : "This doesn't look like a letter from the BIR"
                            }
                        >
                            {filledIn.summary !== '' && (
                                <span className="mb-1.5 block">
                                    {filledIn.summary}
                                </span>
                            )}
                            Check each field against the letter before you save.
                        </Note>
                    )}

                    <SectionCard title="What it says">
                        <div className="flex flex-col gap-4">
                            <div className="flex flex-col gap-1.5">
                                <span className="text-sm font-bold">
                                    What kind of letter?
                                </span>
                                <ChoiceChips
                                    label="What kind of letter?"
                                    value={form.data.kind}
                                    onChange={(value) =>
                                        form.setData('kind', value)
                                    }
                                    options={kinds}
                                />
                                <FieldError message={form.errors.kind} />
                            </div>

                            {namesReturn && (
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="form" className="font-bold">
                                        Return it names
                                    </Label>
                                    <select
                                        id="form"
                                        value={form.data.form}
                                        onChange={(event) =>
                                            form.setData(
                                                'form',
                                                event.target.value,
                                            )
                                        }
                                        className="h-11 rounded-xl border border-input bg-card px-3 text-sm"
                                    >
                                        <option value="">Choose a form</option>
                                        {forms.map((f) => (
                                            <option key={f} value={f}>
                                                {f}
                                            </option>
                                        ))}
                                    </select>
                                    <FieldError message={form.errors.form} />
                                </div>
                            )}

                            {namesPeriod && (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {field('year', 'Year', 'number')}
                                    {monthly ? (
                                        <div className="flex flex-col gap-1.5">
                                            <Label
                                                htmlFor="month"
                                                className="font-bold"
                                            >
                                                Month
                                            </Label>
                                            <select
                                                id="month"
                                                value={form.data.month}
                                                onChange={(event) => {
                                                    const month =
                                                        event.target.value;
                                                    form.setData((data) => ({
                                                        ...data,
                                                        month,
                                                        quarter: month
                                                            ? String(
                                                                  Math.ceil(
                                                                      Number(
                                                                          month,
                                                                      ) / 3,
                                                                  ),
                                                              )
                                                            : '',
                                                    }));
                                                }}
                                                className="h-11 rounded-xl border border-input bg-card px-3 text-sm"
                                            >
                                                <option value="">—</option>
                                                {Array.from(
                                                    { length: 12 },
                                                    (_, i) => (
                                                        <option
                                                            key={i}
                                                            value={i + 1}
                                                        >
                                                            {new Intl.DateTimeFormat(
                                                                'en-US',
                                                                {
                                                                    month: 'long',
                                                                },
                                                            ).format(
                                                                new Date(
                                                                    2000,
                                                                    i,
                                                                    1,
                                                                ),
                                                            )}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </div>
                                    ) : (
                                        !annual && (
                                            <div className="flex flex-col gap-1.5">
                                                <span className="text-sm font-bold">
                                                    Quarter
                                                </span>
                                                <ChoiceChips
                                                    label="Quarter"
                                                    variant="segmented"
                                                    value={form.data.quarter}
                                                    onChange={(value) =>
                                                        form.setData(
                                                            'quarter',
                                                            value,
                                                        )
                                                    }
                                                    options={[
                                                        ['1', 'Q1'],
                                                        ['2', 'Q2'],
                                                        ['3', 'Q3'],
                                                        ['4', 'Q4'],
                                                    ].map(([value, label]) => ({
                                                        value,
                                                        label,
                                                    }))}
                                                />
                                            </div>
                                        )
                                    )}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                {field('dated_on', 'Dated', 'date')}
                                {field(
                                    'reply_by',
                                    'Reply by (if it says)',
                                    'date',
                                )}
                            </div>
                            {field(
                                'office',
                                'From (e.g. Revenue District Office 028)',
                            )}
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="notes" className="font-bold">
                                    Notes{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <textarea
                                    id="notes"
                                    rows={3}
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData(
                                            'notes',
                                            event.target.value,
                                        )
                                    }
                                    className="rounded-xl border border-input bg-card px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <Note title="How this works">
                        You tell us what the letter says, or let the assistant
                        read it; we check it against your records and list what
                        to send or bring. The photo stays on this computer
                        unless you ask the assistant to read it.
                    </Note>
                    <Note tone="warning" title="When to get help">
                        If the letter is a Letter of Authority or mentions an
                        audit or assessment, talk to a CPA or tax lawyer before
                        replying.
                    </Note>
                    <Button
                        type="submit"
                        disabled={form.processing}
                        className="h-12 rounded-xl text-[15px] font-bold"
                    >
                        Save to my documents
                    </Button>
                </div>
            </form>
        </>
    );
}
