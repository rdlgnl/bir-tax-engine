import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { RestorePanel } from '@/components/data/restore-panel';
import { FieldError } from '@/components/field-error';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fileSize, formatMoment } from '@/lib/format';
import { data } from '@/routes';
import { store } from '@/routes/backup';
import type { BackupManifest, DataSummary, LastBackup } from '@/types';

type Props = {
    size: number;
    summary: DataSummary;
    folder: string;
    lastBackup: LastBackup | null;
    pending: BackupManifest | null;
};

export default function Backup({
    size,
    summary,
    folder,
    lastBackup,
    pending,
}: Props) {
    const form = useForm({ password: '', password_confirmation: '' });
    const errors = form.errors as Record<string, string | undefined>;
    const mismatch =
        form.data.password_confirmation.length > 0 &&
        form.data.password !== form.data.password_confirmation;

    return (
        <>
            <Head title="Back up and restore" />

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
                        Back up and restore
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Your data lives only on this device
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit(store(), {
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                        });
                    }}
                    className="flex flex-col gap-4"
                >
                    <SectionCard title="Back up now">
                        <dl className="divide-y divide-divider text-[15px]">
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>What's included</dt>
                                <dd className="text-right font-bold">
                                    All years, documents, settings
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>Size</dt>
                                <dd className="text-right font-bold">
                                    About {fileSize(size)}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt>Saved to</dt>
                                <dd className="text-right font-bold break-all">
                                    {folder} ·{' '}
                                    <Link
                                        href={data().url}
                                        className="text-primary underline"
                                    >
                                        Change
                                    </Link>
                                </dd>
                            </div>
                        </dl>
                        <p className="mt-2 text-[13px] text-muted-foreground">
                            {summary.records > 0
                                ? `${summary.records} records, ${summary.documents} documents and ${summary.filings} filed returns.`
                                : 'Your profile and settings.'}
                        </p>
                    </SectionCard>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="backup-password">Backup password</Label>
                        <Input
                            id="backup-password"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            aria-invalid={errors.password ? true : undefined}
                            className="h-11 rounded-xl bg-card"
                        />
                        <p className="text-[13px] text-muted-foreground">
                            At least 8 characters. Different from your app PIN.
                        </p>
                        <FieldError message={errors.password} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="backup-password-confirmation">
                            Type it again
                        </Label>
                        <Input
                            id="backup-password-confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password_confirmation}
                            onChange={(event) =>
                                form.setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                            aria-invalid={mismatch || undefined}
                            className="h-11 rounded-xl bg-card"
                        />
                        <FieldError
                            message={
                                mismatch
                                    ? 'The two passwords don’t match.'
                                    : undefined
                            }
                        />
                    </div>

                    <Note tone="warning" title="We can't recover this password">
                        There's no account. Without the password, the backup
                        can't be opened by anyone, including us. Write it
                        somewhere safe.
                    </Note>
                    <FieldError message={errors.folder} />
                    {lastBackup && (
                        <Note tone="success" title="Last backup saved">
                            {formatMoment(lastBackup.at)} · {lastBackup.file} ·{' '}
                            {fileSize(lastBackup.size)}
                        </Note>
                    )}

                    <Button
                        type="submit"
                        disabled={
                            form.processing ||
                            mismatch ||
                            form.data.password.length < 8
                        }
                        className="h-12 rounded-xl text-[15px] font-bold"
                    >
                        {form.processing ? 'Backing up…' : 'Back up now'}
                    </Button>
                </form>

                <RestorePanel pending={pending} hasRecords />
            </div>
        </>
    );
}
