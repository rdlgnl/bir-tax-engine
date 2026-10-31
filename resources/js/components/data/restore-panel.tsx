import { router, useForm } from '@inertiajs/react';
import { FieldError } from '@/components/field-error';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, plural } from '@/lib/format';
import { cancel, confirm, upload } from '@/routes/restore';
import type { BackupManifest, DataSummary } from '@/types';

/** "2026: 104 records, 9 certificates, 3 filed returns" */
export function describeSummary(summary: DataSummary): string {
    const years =
        summary.first_year === null
            ? 'No records'
            : summary.first_year === summary.last_year
              ? `${summary.first_year}`
              : `${summary.first_year}–${summary.last_year}`;

    return `${years}: ${plural(summary.records, 'record')}, ${plural(summary.certificates, 'certificate')}, ${plural(summary.filings, 'filed return')}`;
}

/**
 * Restoring a backup: choose the file, see what it holds, then give its
 * password. Restoring replaces everything on this device.
 */
export function RestorePanel({
    pending,
    hasRecords,
}: {
    pending: BackupManifest | null;
    hasRecords: boolean;
}) {
    const file = useForm<{ backup: File | null }>({ backup: null });
    const password = useForm({ password: '' });
    const madeOn = pending ? formatDate(pending.made_at.slice(0, 10)) : null;

    if (!pending) {
        return (
            <SectionCard title="Restore a backup">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        file.submit(upload(), { preserveScroll: true });
                    }}
                    className="flex flex-col gap-3"
                >
                    <Label htmlFor="restore-file">Backup file (.bbk)</Label>
                    <Input
                        id="restore-file"
                        type="file"
                        accept=".bbk"
                        onChange={(event) =>
                            file.setData(
                                'backup',
                                event.target.files?.[0] ?? null,
                            )
                        }
                        aria-invalid={file.errors.backup ? true : undefined}
                        className="h-11 rounded-xl bg-card py-2"
                    />
                    <FieldError message={file.errors.backup} />
                    <Button
                        type="submit"
                        variant="outline"
                        disabled={!file.data.backup || file.processing}
                        className="h-11 rounded-xl font-bold"
                    >
                        {file.processing ? 'Reading…' : 'Open this backup'}
                    </Button>
                </form>
            </SectionCard>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                password.submit(confirm(), { preserveScroll: true });
            }}
            className="flex flex-col gap-4"
        >
            <SectionCard title="Restore a backup">
                <dl className="divide-y divide-divider text-[15px]">
                    <div className="flex justify-between gap-3 py-2.5">
                        <dt className="text-muted-foreground">File</dt>
                        <dd className="text-right font-bold break-all">
                            {pending.file}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-3 py-2.5">
                        <dt className="text-muted-foreground">Made on</dt>
                        <dd className="text-right font-bold">
                            {madeOn} · {pending.device}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-3 py-2.5">
                        <dt className="text-muted-foreground">Inside</dt>
                        <dd className="text-right font-bold">
                            {describeSummary(pending.summary)}
                        </dd>
                    </div>
                </dl>
            </SectionCard>

            <div className="flex flex-col gap-1.5">
                <Label htmlFor="restore-password">Backup password</Label>
                <Input
                    id="restore-password"
                    type="password"
                    autoComplete="off"
                    value={password.data.password}
                    onChange={(event) =>
                        password.setData('password', event.target.value)
                    }
                    aria-invalid={password.errors.password ? true : undefined}
                    className="h-11 rounded-xl bg-card"
                />
                <FieldError message={password.errors.password} />
            </div>

            {hasRecords && (
                <Note tone="danger" title="This replaces what's on this device">
                    Records added here after {madeOn} will be lost. Back up this
                    device first if you're not sure.
                </Note>
            )}
            <Note title="Moving to a new phone or computer">
                Back up on the old device, copy the file over (cable, Drive,
                email to yourself), then restore here.
            </Note>

            <div className="grid grid-cols-2 gap-2.5">
                <Button
                    type="button"
                    variant="outline"
                    className="h-12 rounded-xl text-[15px] font-bold"
                    onClick={() =>
                        router.delete(cancel().url, { preserveScroll: true })
                    }
                >
                    Choose another
                </Button>
                <Button
                    type="submit"
                    variant="outline"
                    disabled={!password.data.password || password.processing}
                    className="h-12 rounded-xl border-2 border-primary text-[15px] font-bold text-primary"
                >
                    {password.processing ? 'Restoring…' : 'Restore this backup'}
                </Button>
            </div>
        </form>
    );
}
