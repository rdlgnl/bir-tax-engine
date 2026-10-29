import { Link, usePage } from '@inertiajs/react';
import { Brand } from '@/components/brand-mark';
import { isActive, navItems } from '@/components/nav-items';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';

export function AppSidebar() {
    const { url, props } = usePage();
    const deadline = props.nextDeadline;

    return (
        <nav
            aria-label="Main"
            className="sticky top-0 hidden h-dvh w-[220px] shrink-0 flex-col gap-1 overflow-y-auto border-r bg-card px-4 py-6 lg:flex"
        >
            <div className="px-2 pb-5">
                <Brand />
            </div>

            {navItems.map((item) =>
                item.href ? (
                    <Link
                        key={item.label}
                        href={item.href}
                        prefetch
                        aria-current={isActive(item, url) ? 'page' : undefined}
                        className={cn(
                            'flex h-11 items-center rounded-[10px] px-3 text-sm font-bold text-slate-700 transition-colors hover:bg-muted',
                            isActive(item, url) &&
                                'bg-accent text-accent-foreground hover:bg-accent',
                        )}
                    >
                        {item.label}
                    </Link>
                ) : (
                    <span
                        key={item.label}
                        aria-disabled="true"
                        title="Coming in a later update"
                        className="flex h-11 cursor-default items-center rounded-[10px] px-3 text-sm font-bold text-slate-400"
                    >
                        {item.label}
                        <span className="sr-only"> (coming soon)</span>
                    </span>
                ),
            )}

            {deadline && (
                <div className="mt-6 flex flex-col gap-1 rounded-xl bg-muted px-3 py-3.5">
                    <span className="text-xs font-extrabold tracking-[0.4px] text-muted-foreground">
                        NEXT DEADLINE
                    </span>
                    <span className="text-[15px] font-extrabold">
                        {formatDate(deadline.date, 'short')}
                    </span>
                    <span className="text-[13px] text-muted-foreground">
                        {deadline.quarter === 4
                            ? 'Annual'
                            : `Q${deadline.quarter}`}{' '}
                        {deadline.form}
                        {deadline.amount !== null &&
                            ` · ${peso(deadline.amount)}`}
                    </span>
                </div>
            )}
        </nav>
    );
}
