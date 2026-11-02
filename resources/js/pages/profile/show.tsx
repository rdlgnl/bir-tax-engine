import { Head, Link, router } from '@inertiajs/react';
import { SectionCard } from '@/components/section-card';
import { ClassificationList } from '@/components/profile/classification-list';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { data, registration } from '@/routes';
import { classification as saveClassification } from '@/routes/profile';
import { show as setupShow } from '@/routes/setup';
import { compare } from '@/routes/tax';
import type { RulePack, TaxProfileSummary } from '@/types';

type Choice = { value: string; label: string };

type Props = {
    profile: TaxProfileSummary;
    classification: {
        year: number;
        assigned: string | null;
        estimate: Choice;
        options: Choice[];
    };
    year: number;
    methodLockedOn: string | null;
    nextYearDecisionDue: string;
    rulePack: RulePack;
};

function maskTin(tin: string | null): string | null {
    return tin ? `•••-•••-${tin.slice(8)}` : null;
}

export default function ProfileShow({
    profile,
    classification,
    year,
    methodLockedOn,
    nextYearDecisionDue,
    rulePack,
}: Props) {
    return (
        <>
            <Head title="Profile & settings" />

            <header className="mb-6 flex flex-wrap items-center gap-4">
                <span className="flex size-14 items-center justify-center rounded-full bg-ink text-lg font-extrabold text-ink-foreground">
                    {profile.initials}
                </span>
                <div>
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight">
                        {profile.full_name}
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        {[
                            maskTin(profile.tin) &&
                                `TIN ${maskTin(profile.tin)}`,
                            profile.rdo_code && `RDO ${profile.rdo_code}`,
                        ]
                            .filter(Boolean)
                            .join(' · ') || 'Not registered with the BIR yet'}
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-2">
                <SectionCard
                    title="Tax profile"
                    action={
                        <Link
                            href={setupShow('income').url}
                            className="text-[13px] font-bold text-primary hover:underline"
                        >
                            Edit answers
                        </Link>
                    }
                >
                    <dl className="mb-5 divide-y divide-divider text-[15px]">
                        <div className="flex justify-between gap-3 py-2.5">
                            <dt className="font-semibold">{year} method</dt>
                            <dd className="text-right">
                                {profile.regime_label ?? 'Not chosen'}
                                {methodLockedOn && (
                                    <span className="block text-[13px] text-muted-foreground">
                                        Locked when Q1 was filed on{' '}
                                        {formatDate(methodLockedOn)}
                                    </span>
                                )}
                            </dd>
                        </div>
                        <div className="flex justify-between gap-3 py-2.5">
                            <dt className="font-semibold">{year + 1} method</dt>
                            <dd className="text-right">
                                Not chosen ·{' '}
                                <Link
                                    href={compare().url}
                                    className="font-bold text-primary hover:underline"
                                >
                                    compare
                                </Link>
                                <span className="block text-[13px] text-muted-foreground">
                                    Decide by your Q1 {year + 1} return (
                                    {formatDate(nextYearDecisionDue)})
                                </span>
                            </dd>
                        </div>
                        {profile.line_of_business && (
                            <div className="flex justify-between gap-3 py-2.5">
                                <dt className="font-semibold">Line of work</dt>
                                <dd className="text-right">
                                    {profile.line_of_business}
                                </dd>
                            </div>
                        )}
                    </dl>
                    <ClassificationList profile={profile} showTodos={false} />
                </SectionCard>

                <div className="flex flex-col gap-5">
                    <SectionCard title="BIR registration (COR)">
                        <dl className="divide-y divide-divider text-[15px]">
                            {profile.registration.map((row) => (
                                <div
                                    key={row.label}
                                    className="flex justify-between gap-3 py-2.5"
                                >
                                    <dt className="font-semibold">
                                        {row.label}
                                    </dt>
                                    <dd className="text-right">
                                        {row.status}
                                        {row.note && (
                                            <span className="block text-[13px] text-warning">
                                                {row.note}
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                        <div className="mt-4 flex flex-col gap-1.5">
                            <label
                                htmlFor="classification"
                                className="text-sm font-bold"
                            >
                                Taxpayer classification for{' '}
                                {classification.year}
                            </label>
                            <select
                                id="classification"
                                value={classification.assigned ?? ''}
                                onChange={(event) =>
                                    router.put(
                                        saveClassification().url,
                                        {
                                            year: classification.year,
                                            classification:
                                                event.target.value || null,
                                        },
                                        { preserveScroll: true },
                                    )
                                }
                                className="h-11 rounded-xl border border-input bg-card px-3 text-sm"
                            >
                                <option value="">
                                    Not set · estimated{' '}
                                    {classification.estimate.label}
                                </option>
                                {classification.options.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                            <p className="text-[13px] text-muted-foreground">
                                The BIR assigns it; ORUS shows yours. Micro and
                                small taxpayers pay lower penalties.
                            </p>
                        </div>
                        <div className="mt-4 flex flex-wrap gap-2.5">
                            <Button
                                asChild
                                variant="outline"
                                className="rounded-xl"
                            >
                                <Link href={setupShow('certificate').url}>
                                    Update COR details
                                </Link>
                            </Button>
                            <Button
                                asChild
                                variant="outline"
                                className="rounded-xl"
                            >
                                <Link href={registration().url}>
                                    Update with Form 1905
                                </Link>
                            </Button>
                        </div>
                    </SectionCard>

                    <SectionCard title="Your data">
                        <p className="text-[15px]">
                            Stored only on this computer. No account.
                        </p>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Back it up to move it or keep it safe, and lock the
                            app with a PIN.
                        </p>
                        <Button
                            asChild
                            variant="outline"
                            className="mt-3 rounded-xl"
                        >
                            <Link href={data().url}>
                                Backup, app lock and erase
                            </Link>
                        </Button>
                        <p className="mt-3 text-[13px] text-muted-foreground">
                            Rule pack {rulePack.version}
                            {rulePack.reviewed
                                ? ' · reviewed'
                                : ' · not yet reviewed by a tax professional'}
                        </p>
                    </SectionCard>
                </div>
            </div>
        </>
    );
}
