import { Check } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** A numbered list of things to do, one dark disc per step. */
export function StepList({ children }: { children: ReactNode }) {
    return <ol className="flex flex-col gap-4">{children}</ol>;
}

/** A step; a done one shows a green tick instead of its number. */
export function Step({
    n,
    title,
    done = false,
    children,
}: {
    n: number;
    title: string;
    done?: boolean;
    children?: ReactNode;
}) {
    return (
        <li className="flex gap-3">
            <span
                className={cn(
                    'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-extrabold',
                    done
                        ? 'bg-success text-white'
                        : 'bg-ink text-ink-foreground',
                )}
            >
                {done ? <Check className="size-4" aria-label="Done" /> : n}
            </span>
            <span className="text-sm">
                <span className="block text-[15px] font-bold">{title}</span>
                {children && (
                    <span className="text-muted-foreground">{children}</span>
                )}
            </span>
        </li>
    );
}
