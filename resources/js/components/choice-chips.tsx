import { cn } from '@/lib/utils';

export type Choice<T extends string> = { value: T; label: string };

type Props<T extends string> = {
    label: string;
    options: Choice<T>[];
    value: T | null;
    onChange: (value: T) => void;
    /** Pills wrap like the design's chips; segmented splits the row evenly. */
    variant?: 'pill' | 'segmented';
    className?: string;
};

/** A single-choice group of buttons, announced as a radio group. */
export function ChoiceChips<T extends string>({
    label,
    options,
    value,
    onChange,
    variant = 'pill',
    className,
}: Props<T>) {
    return (
        <div
            role="radiogroup"
            aria-label={label}
            className={cn(
                variant === 'pill'
                    ? 'flex flex-wrap gap-2'
                    : 'grid auto-cols-fr grid-flow-col gap-1 rounded-xl bg-muted p-1',
                className,
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
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'text-sm font-bold transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                            variant === 'pill'
                                ? 'h-[38px] rounded-full border border-input bg-card px-3.5 hover:bg-muted'
                                : 'h-10 rounded-[10px] hover:bg-card/60',
                            checked &&
                                (variant === 'pill'
                                    ? 'border-primary bg-selected text-accent-foreground ring-1 ring-primary hover:bg-selected'
                                    : 'bg-card shadow-sm hover:bg-card'),
                        )}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
