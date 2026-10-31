import { Head, Link, router } from '@inertiajs/react';
import {
    CalendarClock,
    Download,
    FileCheck2,
    Scale,
    TrendingUp,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { data } from '@/routes';
import { read } from '@/routes/notifications';

type Item = {
    key: string;
    kind: string;
    title: string;
    detail: string | null;
    url: string | null;
    at: string;
};

const icons: Record<string, { icon: LucideIcon; tone: string }> = {
    due: { icon: CalendarClock, tone: 'bg-warning-soft text-warning' },
    backup: { icon: Download, tone: 'bg-warning-soft text-warning' },
    certificate: { icon: FileCheck2, tone: 'bg-accent text-primary' },
    estimate: { icon: TrendingUp, tone: 'bg-accent text-primary' },
    filing: { icon: FileCheck2, tone: 'bg-success-soft text-success' },
    rules: { icon: Scale, tone: 'bg-accent text-primary' },
};

function when(at: string): string {
    const date = at.slice(0, 10);
    const today = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return date ===
        `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`
        ? 'Today'
        : formatDate(date);
}

export default function Notifications({
    new: fresh,
    earlier,
}: {
    new: Item[];
    earlier: Item[];
}) {
    return (
        <>
            <Head title="Notifications" />

            <div className="mx-auto max-w-[880px]">
                <header className="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-[28px] leading-tight font-extrabold tracking-tight sm:text-[30px]">
                        Notifications
                    </h1>
                    <div className="flex gap-2.5">
                        <Button
                            variant="outline"
                            className="rounded-xl font-bold"
                            onClick={() =>
                                router.post(
                                    read().url,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            Mark all read
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="rounded-xl font-bold"
                        >
                            <Link href={data().url}>Settings</Link>
                        </Button>
                    </div>
                </header>

                <h2 className="mb-2 text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                    New
                </h2>
                {fresh.length === 0 ? (
                    <p className="mb-6 rounded-card border bg-card p-5 text-[15px] text-muted-foreground">
                        Nothing new. Deadlines, missing 2307s and backups show
                        up here when they need you.
                    </p>
                ) : (
                    <ul className="mb-6 flex flex-col gap-3">
                        {fresh.map((item) => {
                            const { icon: Icon, tone } =
                                icons[item.kind] ?? icons.rules;
                            const body = (
                                <>
                                    <span
                                        className={`flex size-10 shrink-0 items-center justify-center rounded-xl ${tone}`}
                                    >
                                        <Icon className="size-5" aria-hidden />
                                    </span>
                                    <span className="flex-1">
                                        <span className="block text-[17px] font-bold">
                                            {item.title}
                                        </span>
                                        {item.detail && (
                                            <span className="text-sm text-muted-foreground">
                                                {item.detail}
                                            </span>
                                        )}
                                    </span>
                                    <span className="shrink-0 text-sm text-muted-foreground">
                                        {when(item.at)}
                                    </span>
                                </>
                            );

                            return (
                                <li key={item.key}>
                                    {item.url ? (
                                        <Link
                                            href={item.url}
                                            className="flex items-center gap-4 rounded-card border border-[#C9D7FB] bg-card p-5 hover:bg-selected"
                                        >
                                            {body}
                                        </Link>
                                    ) : (
                                        <div className="flex items-center gap-4 rounded-card border bg-card p-5">
                                            {body}
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                )}

                {earlier.length > 0 && (
                    <>
                        <h2 className="mb-2 text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                            Earlier
                        </h2>
                        <SectionCard>
                            <ul className="-my-2 divide-y divide-divider">
                                {earlier.map((item) => (
                                    <li
                                        key={item.key}
                                        className="flex items-center justify-between gap-3 py-3.5"
                                    >
                                        <span>
                                            {item.url ? (
                                                <Link
                                                    href={item.url}
                                                    className="text-[15px] font-semibold hover:underline"
                                                >
                                                    {item.title}
                                                </Link>
                                            ) : (
                                                <span className="text-[15px] font-semibold">
                                                    {item.title}
                                                </span>
                                            )}
                                            {item.detail && (
                                                <span className="block text-[13px] text-muted-foreground">
                                                    {item.detail}
                                                </span>
                                            )}
                                        </span>
                                        <span className="shrink-0 text-sm text-muted-foreground">
                                            {when(item.at)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </SectionCard>
                    </>
                )}
            </div>
        </>
    );
}
