import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { FieldError } from '@/components/field-error';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatMoment, plural } from '@/lib/format';
import { backup, data } from '@/routes';
import { destroy } from '@/routes/erase';
import type { DataSummary, LastBackup } from '@/types';

type Props = {
    summary: DataSummary;
    lastBackup: LastBackup | null;
    retentionYears: number;
};

export default function Erase({ summary, lastBackup, retentionYears }: Props) {
    const form = useForm({ confirmation: '' });
    const years =
        summary.first_year === null
            ? 'None yet'
            : summary.first_year === summary.last_year
              ? `${summary.first_year}`
              : `${summary.first_year}–${summary.last_year}`;

    return (
        <>
            <Head title="Erase all data" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={data().url}
                    aria-label="Back to Your data"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Erase all data
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Removes everything from this device
                    </p>
                </div>
            </header>

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(destroy());
                }}
                className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]"
            >
                <div className="flex flex-col gap-4">
                    <Note tone="danger" title="This can't be undone">
                        There's no account and no copy on a server. Once erased,
                        only a backup file can bring your data back.
                    </Note>

                    <SectionCard title="What will be erased">
                        <dl className="divide-y divide-divider text-[15px]">
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>Records</dt>
                                <dd className="text-right font-bold">
                                    {years} · {plural(summary.records, 'item')}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>Documents</dt>
                                <dd className="text-right font-bold">
                                    {plural(
                                        summary.certificates,
                                        'certificate',
                                    )}
                                    {summary.documents > 0 &&
                                        ` · ${plural(summary.documents, 'scan')}`}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>Filed returns and confirmations</dt>
                                <dd className="text-right font-bold">
                                    {summary.filings}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>Profile and settings</dt>
                                <dd className="text-right font-bold">All</dd>
                            </div>
                        </dl>
                    </SectionCard>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="erase-confirmation">
                            Type ERASE to confirm
                        </Label>
                        <Input
                            id="erase-confirmation"
                            autoComplete="off"
                            value={form.data.confirmation}
                            onChange={(event) =>
                                form.setData('confirmation', event.target.value)
                            }
                            aria-invalid={
                                form.errors.confirmation ? true : undefined
                            }
                            className="h-11 rounded-xl bg-card"
                        />
                        <FieldError message={form.errors.confirmation} />
                    </div>

                    <Note
                        tone="warning"
                        title={`The BIR can ask for records for ${retentionYears} years`}
                    >
                        Keep a backup or export before erasing, especially for
                        years you've filed.
                    </Note>
                </div>

                <div className="flex flex-col gap-4">
                    <SectionCard title="Your last backup">
                        {lastBackup ? (
                            <dl className="divide-y divide-divider text-[15px]">
                                <div className="flex justify-between gap-3 py-2.5">
                                    <dt>When</dt>
                                    <dd className="text-right font-bold">
                                        {formatMoment(lastBackup.at)}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-3 py-2.5">
                                    <dt>File</dt>
                                    <dd className="text-right font-bold break-all">
                                        {lastBackup.file}
                                    </dd>
                                </div>
                            </dl>
                        ) : (
                            <p className="text-[15px] text-warning">
                                You haven't backed up on this device.
                            </p>
                        )}
                    </SectionCard>
                    <Note title="Selling or giving away this device?">
                        Erase here, then also reset the phone or computer
                        itself.
                    </Note>

                    <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-1">
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <Link href={backup().url}>Back up first</Link>
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={
                                form.data.confirmation !== 'ERASE' ||
                                form.processing
                            }
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            Erase everything
                        </Button>
                    </div>
                </div>
            </form>
        </>
    );
}
