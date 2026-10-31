import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { RestorePanel } from '@/components/data/restore-panel';
import { welcome } from '@/routes';
import type { BackupManifest } from '@/types';

/** Restoring from the welcome screen, on a device with nothing on it yet. */
export default function Restore({
    pending,
}: {
    pending: BackupManifest | null;
}) {
    return (
        <>
            <Head title="Restore a backup" />

            <div className="mx-auto max-w-[520px] px-4 py-10">
                <header className="mb-6 flex items-start gap-3.5">
                    <Link
                        href={welcome().url}
                        aria-label="Back to Welcome"
                        className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                    >
                        <ChevronLeft className="size-5" aria-hidden />
                    </Link>
                    <div>
                        <h1 className="text-[28px] leading-tight font-extrabold tracking-tight">
                            Restore a backup
                        </h1>
                        <p className="text-[15px] text-muted-foreground">
                            Bring your records over from another device
                        </p>
                    </div>
                </header>

                <RestorePanel pending={pending} hasRecords={false} />
            </div>
        </>
    );
}
