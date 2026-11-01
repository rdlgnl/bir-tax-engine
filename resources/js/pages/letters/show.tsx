import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, FileText } from 'lucide-react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { destroy, file, index, resolve } from '@/routes/letters';
import type { BirLetter, LetterCheck } from '@/types';

type Props = { letter: BirLetter; check: LetterCheck };

const tones: Record<LetterCheck['result'], 'success' | 'warning' | 'info'> = {
    filed: 'success',
    not_needed: 'success',
    missing: 'warning',
    income: 'info',
    none: 'info',
};

export default function LetterShow({ letter, check }: Props) {
    const rows: [string, string][] = [
        ['Type', letter.kind_label],
        ...(letter.form
            ? ([['Return named', `${letter.form}, ${letter.period}`]] as [
                  string,
                  string,
              ][])
            : letter.period
              ? ([['Period', letter.period]] as [string, string][])
              : []),
        [
            'Reply by',
            letter.reply_by
                ? formatDate(letter.reply_by, 'full')
                : 'Not stated',
        ],
    ];

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
                        Kept on this device · nothing is sent
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    <SectionCard className="flex items-center gap-4 p-4">
                        <span className="flex size-[90px] shrink-0 items-center justify-center rounded-xl bg-[#E3E7EC] text-slate-600">
                            <FileText className="size-6" aria-hidden />
                        </span>
                        <div className="min-w-0">
                            <p className="text-[15px] font-bold">
                                {letter.has_file ? 'Letter scanned' : 'Letter'}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {[
                                    letter.office,
                                    letter.dated_on &&
                                        `dated ${formatDate(letter.dated_on, 'long')}`,
                                ]
                                    .filter(Boolean)
                                    .join(' · ') || 'No date or office noted'}
                            </p>
                            {letter.has_file && (
                                <a
                                    href={file(letter.id).url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="text-sm font-bold text-primary hover:underline"
                                >
                                    {letter.original_filename ??
                                        'Open the photo'}
                                </a>
                            )}
                        </div>
                    </SectionCard>

                    <SectionCard title="What it says, in plain words">
                        <dl className="text-[15px]">
                            {rows.map(([label, value]) => (
                                <div
                                    key={label}
                                    className="flex justify-between gap-3 border-b border-divider py-3 last:border-0"
                                >
                                    <dt>{label}</dt>
                                    <dd className="text-right font-bold">
                                        {value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                        {letter.notes && (
                            <p className="mt-2 text-sm whitespace-pre-line text-muted-foreground">
                                {letter.notes}
                            </p>
                        )}
                    </SectionCard>

                    {check.result !== 'none' && (
                        <Note tone={tones[check.result]} title={check.title}>
                            {check.detail}{' '}
                            {check.url && (
                                <Link
                                    href={check.url}
                                    className="font-bold underline"
                                >
                                    {check.result === 'missing'
                                        ? 'File it now'
                                        : check.result === 'income'
                                          ? 'See your records'
                                          : check.result === 'not_needed' &&
                                              letter.form === '2551Q'
                                            ? `See Q1 ${letter.period.slice(-4)}`
                                            : 'See it'}
                                </Link>
                            )}
                        </Note>
                    )}
                </div>

                <div className="flex flex-col gap-4">
                    {check.documents.length > 0 && (
                        <SectionCard title="What to send or bring">
                            <ul className="divide-y divide-divider">
                                {check.documents.map((doc) => (
                                    <li
                                        key={doc.title}
                                        className="flex items-center justify-between gap-3 py-3"
                                    >
                                        <span>
                                            <span className="block text-[15px] font-bold">
                                                {doc.url ? (
                                                    <Link
                                                        href={doc.url}
                                                        className="hover:underline"
                                                    >
                                                        {doc.title}
                                                    </Link>
                                                ) : (
                                                    doc.title
                                                )}
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                {doc.detail}
                                            </span>
                                        </span>
                                        <span className="text-sm font-bold whitespace-nowrap">
                                            {doc.status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </SectionCard>
                    )}

                    <Note
                        tone={letter.needs_help ? 'danger' : 'warning'}
                        title={
                            letter.needs_help
                                ? 'Talk to a professional first'
                                : 'When to get help'
                        }
                    >
                        {letter.needs_help
                            ? 'This letter opens an audit or assesses tax. Talk to a CPA or tax lawyer before replying, and note its deadlines.'
                            : 'If the letter is a Letter of Authority or mentions an audit or assessment, talk to a CPA or tax lawyer before replying.'}
                    </Note>

                    {letter.resolved_on ? (
                        <Note tone="success" title="Dealt with">
                            Marked on {formatDate(letter.resolved_on, 'long')}.
                        </Note>
                    ) : (
                        <Button
                            className="h-12 rounded-xl text-[15px] font-bold"
                            onClick={() =>
                                router.post(resolve(letter.id).url, undefined, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            I've replied · mark as dealt with
                        </Button>
                    )}
                    <Button
                        variant="ghost"
                        className="text-destructive"
                        onClick={() => {
                            if (
                                window.confirm(
                                    'Delete this letter and its photo?',
                                )
                            ) {
                                router.delete(destroy(letter.id).url);
                            }
                        }}
                    >
                        Delete letter
                    </Button>
                </div>
            </div>
        </>
    );
}
