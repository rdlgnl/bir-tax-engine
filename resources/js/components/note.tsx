import { AlertTriangle, Info } from 'lucide-react';
import type { ReactNode } from 'react';

/** The design's side notes: blue for information, amber for a warning. */
export function Note({
    title,
    tone = 'info',
    children,
}: {
    title: ReactNode;
    tone?: 'info' | 'warning';
    children: ReactNode;
}) {
    const Icon = tone === 'warning' ? AlertTriangle : Info;

    return (
        <div
            className={
                tone === 'warning'
                    ? 'flex items-start gap-2.5 rounded-2xl border border-warning-border bg-warning-soft px-4 py-3.5 text-sm text-warning'
                    : 'flex items-start gap-2.5 rounded-2xl border border-[#C9D7FB] bg-selected px-4 py-3.5 text-sm text-accent-foreground'
            }
        >
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
            <div>
                <p className="text-[15px] font-bold">{title}</p>
                {children}
            </div>
        </div>
    );
}
