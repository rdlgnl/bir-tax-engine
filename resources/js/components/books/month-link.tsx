import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { BooksPeriod } from '@/types';

/** Previous or next month, greyed out where there's none. */
export function MonthLink({
    to,
    href,
    label,
    children,
}: {
    to: BooksPeriod | null;
    href: (to: BooksPeriod) => string;
    label: string;
    children: ReactNode;
}) {
    const className =
        'flex size-10 items-center justify-center rounded-xl border bg-card';

    if (!to) {
        return (
            <span
                aria-disabled="true"
                aria-label={label}
                className={`${className} text-slate-300`}
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={href(to)}
            aria-label={label}
            className={`${className} hover:bg-muted`}
        >
            {children}
        </Link>
    );
}
