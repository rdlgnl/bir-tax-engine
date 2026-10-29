import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** The design's white card: 22px corners, hairline border. */
export function SectionCard({
    title,
    action,
    children,
    className,
    as: Tag = 'section',
}: {
    title?: ReactNode;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
    as?: 'section' | 'div';
}) {
    return (
        <Tag className={cn('rounded-card border bg-card p-6', className)}>
            {(title || action) && (
                <div className="mb-3 flex items-center justify-between gap-3">
                    {title && (
                        <h2 className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                            {title}
                        </h2>
                    )}
                    {action}
                </div>
            )}
            {children}
        </Tag>
    );
}

export function CardLink({
    href,
    children,
}: {
    href: string;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            className="text-[13px] font-bold text-primary hover:underline"
        >
            {children}
        </Link>
    );
}
