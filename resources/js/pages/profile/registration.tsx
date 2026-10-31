import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { useState } from 'react';
import { DownloadCard } from '@/components/download-card';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Step, StepList } from '@/components/step-list';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import { profile } from '@/routes';
import { form } from '@/routes/registration';
import { show as setupShow } from '@/routes/setup';

type Update = {
    key: string;
    label: string;
    detail: string;
    status: 'suggested' | 'not_needed' | 'optional' | 'orus';
};

type Props = {
    year: number;
    corOutdated: boolean;
    updates: Update[];
    edition: string;
};

const statusLabels: Record<Update['status'], string> = {
    suggested: 'Suggested',
    not_needed: 'Not needed',
    optional: '—',
    orus: 'Online in ORUS',
};

export default function Registration({
    year,
    corOutdated,
    updates,
    edition,
}: Props) {
    const [checks, setChecks] = useState({ cor: false, return: false });

    return (
        <>
            <Head title="Update your registration" />

            <header className="mb-6 flex items-start gap-3.5">
                <Link
                    href={profile().url}
                    aria-label="Back to Profile & settings"
                    className="flex size-[46px] shrink-0 items-center justify-center rounded-xl border bg-card hover:bg-muted"
                >
                    <ChevronLeft className="size-5" aria-hidden />
                </Link>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Update your registration
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        BIR Form 1905 · from your COR and your choices
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    {corOutdated && (
                        <Note
                            tone="warning"
                            title="Your COR still lists percentage tax"
                        >
                            You chose 8% for {year}, which replaces percentage
                            tax. Leaving it on your COR can trigger BIR letters
                            about "unfiled" 2551Q returns.
                        </Note>
                    )}

                    <SectionCard title="Updates we can prepare">
                        <ul className="divide-y divide-divider">
                            {updates.map((update) => (
                                <li
                                    key={update.key}
                                    className="flex items-center justify-between gap-4 py-3"
                                >
                                    <span>
                                        <span className="block text-[15px] font-bold">
                                            {update.label}
                                        </span>
                                        <span className="text-[13px] text-muted-foreground">
                                            {update.detail}
                                        </span>
                                    </span>
                                    <span
                                        className={cn(
                                            'shrink-0 text-sm font-bold',
                                            update.status === 'suggested' &&
                                                'text-warning',
                                        )}
                                    >
                                        {statusLabels[update.status]}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </SectionCard>

                    {corOutdated && (
                        <SectionCard>
                            <h2 className="mb-3 text-lg font-extrabold">
                                Check before you sign
                            </h2>
                            <label className="flex items-center gap-3 py-1.5 text-[15px]">
                                <Checkbox
                                    checked={checks.cor}
                                    onCheckedChange={(c) =>
                                        setChecks({
                                            ...checks,
                                            cor: c === true,
                                        })
                                    }
                                />
                                My COR is attached
                            </label>
                            <label className="flex items-center gap-3 py-1.5 text-[15px]">
                                <Checkbox
                                    checked={checks.return}
                                    onCheckedChange={(c) =>
                                        setChecks({
                                            ...checks,
                                            return: c === true,
                                        })
                                    }
                                />
                                My Q1 {year} 1701Q (8% elected) is attached
                            </label>
                            <p className="mt-2 text-[13px] text-muted-foreground">
                                Keep the BIR-received copy and your new COR.
                                They answer letters about "unfiled" 2551Q
                                returns.
                            </p>
                        </SectionCard>
                    )}
                </div>

                <div className="flex flex-col gap-4">
                    {corOutdated ? (
                        <DownloadCard
                            href={form().url}
                            title="Download filled 1905"
                            detail={`Official BIR form (${edition}) for the update, filled for you.`}
                        />
                    ) : (
                        <Note title="Nothing to update right now">
                            Your registration matches your choices in the app.
                        </Note>
                    )}

                    <SectionCard title="How to submit">
                        <StepList>
                            <Step n={1} title="Try ORUS first">
                                Some updates are done online, such as taxpayer
                                classification
                            </Step>
                            <Step n={2} title="Otherwise, file at your RDO">
                                Bring the form, your COR and the Q1 1701Q where
                                you chose 8%
                            </Step>
                            <Step n={3} title="Scan the new COR">
                                <Link
                                    href={setupShow('certificate').url}
                                    className="font-bold text-primary hover:underline"
                                >
                                    Update your tax profile from it
                                </Link>
                            </Step>
                        </StepList>
                    </SectionCard>

                    <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-1">
                        <Button
                            asChild
                            variant="outline"
                            className="h-12 rounded-xl text-[15px] font-bold"
                        >
                            <Link href={profile().url}>Not now</Link>
                        </Button>
                        {corOutdated && (
                            <Button
                                asChild
                                className="h-12 rounded-xl text-[15px] font-bold"
                            >
                                <a href={form().url} download>
                                    Prepare the update
                                </a>
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
