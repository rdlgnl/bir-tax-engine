import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type Option<T extends string> = {
    value: T;
    title: ReactNode;
    description?: ReactNode;
    badge?: string;
    disabled?: boolean;
    children?: ReactNode;
};

type Props<T extends string> = {
    label: string;
    options: Option<T>[];
    value: T | null;
    onChange: (value: T) => void;
    columns?: 2 | 3;
    showRadio?: boolean;
};

/** Big answer cards for the setup questions, announced as a radio group. */
export function OptionCards<T extends string>({
    label,
    options,
    value,
    onChange,
    columns = 3,
    showRadio = false,
}: Props<T>) {
    return (
        <div
            role="radiogroup"
            aria-label={label}
            className={cn(
                'grid gap-3.5',
                columns === 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2',
            )}
        >
            {options.map((option) => {
                const checked = option.value === value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        role="radio"
                        aria-checked={checked}
                        disabled={option.disabled}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'flex flex-col items-start gap-1 rounded-2xl border bg-card p-5 text-left transition-colors hover:border-slate-400 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                            checked &&
                                'border-2 border-primary bg-selected p-[19px] hover:border-primary',
                        )}
                    >
                        <span className="flex w-full items-center gap-2.5">
                            {showRadio && (
                                <span
                                    aria-hidden
                                    className={cn(
                                        'flex size-5 shrink-0 items-center justify-center rounded-full border-2 border-slate-400',
                                        checked && 'border-primary',
                                    )}
                                >
                                    {checked && (
                                        <span className="size-2.5 rounded-full bg-primary" />
                                    )}
                                </span>
                            )}
                            <span className="text-lg font-extrabold">
                                {option.title}
                            </span>
                            {option.badge && (
                                <span className="ml-auto rounded-full bg-accent px-2.5 py-1 text-xs font-extrabold text-accent-foreground">
                                    {option.badge}
                                </span>
                            )}
                        </span>
                        {option.description && (
                            <span className="text-[15px] text-muted-foreground">
                                {option.description}
                            </span>
                        )}
                        {option.children}
                    </button>
                );
            })}
        </div>
    );
}
