import { AlertCircle, AlertTriangle, Check, Info } from 'lucide-react';
import type { ReactNode } from 'react';

type Tone = 'info' | 'warning' | 'danger' | 'success';

const tones: Record<Tone, { icon: typeof Info; className: string }> = {
    info: {
        icon: Info,
        className: 'border-[#C9D7FB] bg-selected text-accent-foreground',
    },
    warning: {
        icon: AlertTriangle,
        className: 'border-warning-border bg-warning-soft text-warning',
    },
    danger: {
        icon: AlertCircle,
        className: 'border-red-200 bg-red-50 text-destructive',
    },
    success: {
        icon: Check,
        className: 'border-green-200 bg-success-soft text-success',
    },
};

/** The design's side notes: blue for information, amber for a warning, red for something that can't be undone, green for done. */
export function Note({
    title,
    tone = 'info',
    children,
}: {
    title: ReactNode;
    tone?: Tone;
    children: ReactNode;
}) {
    const { icon: Icon, className } = tones[tone];

    return (
        <div
            className={`flex items-start gap-2.5 rounded-2xl border px-4 py-3.5 text-sm ${className}`}
        >
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
            <div>
                <p className="text-[15px] font-bold">{title}</p>
                {children}
            </div>
        </div>
    );
}
