import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { Note } from '@/components/note';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import { data } from '@/routes';
import { seen } from '@/routes/rules';

type Numbers = {
    quarter_label: string;
    quarter: number | null;
    annual: number | null;
    next_deadline: string | null;
};

type Props = {
    version: string;
    reviewed: boolean;
    changes: { area: string; title: string; summary: string }[];
    pending: boolean;
    previousVersion: string | null;
    before: Numbers | null;
    after: Numbers;
};

const money = (amount: number | null) => (amount === null ? '—' : peso(amount));
const day = (date: string | null) =>
    date === null ? '—' : formatDate(date, 'short');

export default function Rules({
    version,
    reviewed,
    changes,
    pending,
    previousVersion,
    before,
    after,
}: Props) {
    const unchanged =
        before !== null &&
        before.quarter === after.quarter &&
        before.annual === after.annual &&
        before.next_deadline === after.next_deadline;
    const quarterRow: [string, string, string] = [
        `${after.quarter_label} estimate`,
        money(before?.quarter ?? null),
        money(after.quarter),
    ];
    const rows: [string, string, string][] = [
        // In Q4 the quarter is the annual return, so it isn't listed twice.
        ...(after.quarter_label.startsWith('Q4') ? [] : [quarterRow]),
        ['Annual estimate', money(before?.annual ?? null), money(after.annual)],
        [
            'Next deadline',
            day(before?.next_deadline ?? null),
            day(after.next_deadline),
        ],
    ];

    return (
        <>
            <Head title="BIR rules" />

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
                        {pending ? 'Rule update installed' : 'BIR rules'}
                    </h1>
                    <p className="text-[15px] text-muted-foreground">
                        Rule pack {version}
                        {reviewed
                            ? ' · reviewed by a tax professional'
                            : ' · not yet reviewed by a tax professional'}
                    </p>
                </div>
            </header>

            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div className="flex flex-col gap-5">
                    {pending && before && (
                        <Note
                            tone={unchanged ? 'success' : 'warning'}
                            title={
                                unchanged
                                    ? "Your numbers don't change"
                                    : 'Your numbers changed'
                            }
                        >
                            {unchanged
                                ? `We re-ran ${new Date().getFullYear()} with the new pack: your estimates and next deadline stay the same.`
                                : 'Compare them below. Filed returns keep the figures they were filed with.'}
                        </Note>
                    )}

                    <SectionCard title="What's in this pack">
                        <ul className="divide-y divide-divider">
                            {changes.map((change) => (
                                <li
                                    key={change.title}
                                    className="flex items-start justify-between gap-4 py-3"
                                >
                                    <span>
                                        <span className="block text-[15px] font-bold">
                                            {change.title}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {change.summary}
                                        </span>
                                    </span>
                                    <span className="shrink-0 rounded-full bg-muted px-2.5 py-1 text-xs font-bold">
                                        {change.area}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </SectionCard>

                    <SectionCard
                        title={
                            pending && before
                                ? `Your ${new Date().getFullYear()}, before and after`
                                : `Your ${new Date().getFullYear()} under this pack`
                        }
                    >
                        <table className="w-full text-[15px] tabular-nums">
                            {pending && before && (
                                <thead>
                                    <tr className="border-b text-left text-xs font-bold text-slate-600">
                                        <th className="py-2" />
                                        <th className="py-2 text-right">
                                            Pack {previousVersion}
                                        </th>
                                        <th className="py-2 text-right">
                                            Pack {version}
                                        </th>
                                    </tr>
                                </thead>
                            )}
                            <tbody>
                                {rows.map(([label, was, now]) => (
                                    <tr
                                        key={label}
                                        className="border-b border-divider last:border-0"
                                    >
                                        <td className="py-3">{label}</td>
                                        {pending && before && (
                                            <td className="py-3 text-right">
                                                {was}
                                            </td>
                                        )}
                                        <td className="py-3 text-right font-bold">
                                            {now}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </SectionCard>
                </div>

                <div className="flex flex-col gap-4">
                    <Note title="Filed quarters never change">
                        Returns you marked filed keep the rules they were
                        computed with. If a new rule affects one, we'll suggest
                        an amendment; we never change it silently.
                    </Note>
                    <Note title="How rules arrive">
                        New rule packs come with app updates and apply right
                        away. Nothing about you is sent anywhere.
                    </Note>
                    {pending && (
                        <Button
                            className="h-12 rounded-xl text-[15px] font-bold"
                            onClick={() => router.post(seen().url)}
                        >
                            Got it
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
