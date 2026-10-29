import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import { cleanAmount } from '@/lib/format';
import { cn } from '@/lib/utils';

type Props = Omit<ComponentProps<'input'>, 'value' | 'onChange' | 'size'> & {
    value: string;
    onValueChange: (value: string) => void;
    size?: 'default' | 'large';
    trailing?: React.ReactNode;
};

/** A peso amount. Keeps only digits and up to two decimals. */
export function MoneyInput({
    value,
    onValueChange,
    size = 'default',
    trailing,
    className,
    ...props
}: Props) {
    return (
        <div className="relative">
            <span
                aria-hidden
                className={cn(
                    'pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 font-bold text-slate-500',
                    size === 'large' ? 'text-xl' : 'text-[15px]',
                )}
            >
                ₱
            </span>
            <Input
                inputMode="decimal"
                autoComplete="off"
                value={value}
                onChange={(event) =>
                    onValueChange(cleanAmount(event.target.value))
                }
                className={cn(
                    'rounded-xl bg-card',
                    size === 'large'
                        ? 'h-12 pl-9 text-[22px] font-extrabold md:text-[22px]'
                        : 'h-11 pl-8 text-[15px] md:text-[15px]',
                    trailing && 'pr-32',
                    className,
                )}
                {...props}
            />
            {trailing && (
                <div className="absolute top-1/2 right-2 -translate-y-1/2">
                    {trailing}
                </div>
            )}
        </div>
    );
}
