import { Head, Link, router, useForm } from '@inertiajs/react';
import { AppLockCard } from '@/components/data/app-lock-card';
import { AssistantCard } from '@/components/data/assistant-card';
import type { AssistantSettings } from '@/components/data/assistant-card';
import { FieldError } from '@/components/field-error';
import { ReportMistakeLink } from '@/components/report-mistake-link';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fileSize, plural } from '@/lib/format';
import { backup, erase, rules } from '@/routes';
import { update } from '@/routes/data';
import type { LastBackup } from '@/types';

type Props = {
    size: number;
    folder: string;
    lastBackup: (LastBackup & { days_since: number }) | null;
    remindMonthly: boolean;
    rulePack: { version: string; reviewed: boolean };
    lock: { enabled: boolean; lock_after: number; options: number[] };
    reminders: boolean;
    assistant: AssistantSettings;
};

export default function YourData({
    size,
    folder,
    lastBackup,
    remindMonthly,
    rulePack,
    lock,
    reminders,
    assistant,
}: Props) {
    const folderForm = useForm({ backup_folder: folder });
    const folderChanged = folderForm.data.backup_folder !== folder;

    return (
        <>
            <Head title="Your data" />

            <header className="mb-6">
                <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                    Your data
                </h1>
                <p className="text-[15px] text-muted-foreground">
                    Stored only on this computer · about {fileSize(size)} · no
                    account
                </p>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-2">
                <SectionCard title="Backup and restore">
                    <p className="text-lg font-extrabold">
                        {lastBackup === null
                            ? 'No backup yet'
                            : lastBackup.days_since === 0
                              ? 'Last backup today'
                              : `Last backup ${plural(lastBackup.days_since, 'day')} ago`}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        With no account, a backup is the only way to recover
                        your data or move it to another device. The file is
                        encrypted with a password you choose.
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2.5">
                        <Button asChild className="rounded-xl font-bold">
                            <Link href={backup().url}>Back up now</Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="rounded-xl font-bold"
                        >
                            <Link href={backup().url}>Restore a backup</Link>
                        </Button>
                    </div>

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            folderForm.submit(update(), {
                                preserveScroll: true,
                            });
                        }}
                        className="mt-5 flex flex-col gap-1.5 border-t border-divider pt-4"
                    >
                        <Label htmlFor="backup-folder">Save backups to</Label>
                        <div className="flex gap-2">
                            <Input
                                id="backup-folder"
                                value={folderForm.data.backup_folder}
                                onChange={(event) =>
                                    folderForm.setData(
                                        'backup_folder',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={
                                    folderForm.errors.backup_folder
                                        ? true
                                        : undefined
                                }
                                className="h-11 rounded-xl bg-card"
                            />
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={
                                    !folderChanged || folderForm.processing
                                }
                                className="h-11 rounded-xl font-bold"
                            >
                                Change
                            </Button>
                        </div>
                        <p className="text-[13px] text-muted-foreground">
                            A folder on this computer. A synced folder (Drive,
                            iCloud, Dropbox) keeps a copy off this device too.
                        </p>
                        <FieldError message={folderForm.errors.backup_folder} />
                    </form>

                    <label className="mt-4 flex items-center gap-3 border-t border-divider pt-4 text-[15px] font-semibold">
                        <Checkbox
                            checked={remindMonthly}
                            onCheckedChange={(checked) =>
                                router.put(
                                    update().url,
                                    { backup_reminder: checked === true },
                                    { preserveScroll: true },
                                )
                            }
                        />
                        Remind me monthly
                    </label>
                </SectionCard>

                <div className="flex flex-col gap-5">
                    <SectionCard title="BIR rules">
                        <p className="text-[15px] font-bold">
                            Rule pack {rulePack.version}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {rulePack.reviewed
                                ? 'Reviewed by a tax professional.'
                                : 'Not yet reviewed by a tax professional.'}{' '}
                            New rules arrive with app updates; nothing about you
                            is sent anywhere. BuwisBuddy is open source: if a
                            rule looks wrong,{' '}
                            <ReportMistakeLink
                                packVersion={rulePack.version}
                                label="report it"
                            />
                            .
                        </p>
                        <Link
                            href={rules().url}
                            className="mt-2 inline-block text-sm font-bold text-primary hover:underline"
                        >
                            What's in this pack
                        </Link>
                    </SectionCard>

                    <AppLockCard lock={lock} reminders={reminders} />

                    <AssistantCard settings={assistant} />

                    <SectionCard title="Erase all data">
                        <p className="text-sm text-muted-foreground">
                            Removes everything from this computer. Only a backup
                            can bring it back.
                        </p>
                        <Button
                            asChild
                            variant="outline"
                            className="mt-3 rounded-xl border-destructive font-bold text-destructive"
                        >
                            <Link href={erase().url}>Erase all data</Link>
                        </Button>
                    </SectionCard>
                </div>
            </div>
        </>
    );
}
