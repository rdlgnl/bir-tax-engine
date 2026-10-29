import { cn } from '@/lib/utils';

export function BrandMark({ className }: { className?: string }) {
    return (
        <div
            className={cn(
                'flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-ink',
                className,
            )}
        >
            <svg
                aria-hidden="true"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#FFFFFF"
                strokeWidth="2.2"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M4 19h16" />
                <path d="M6 15V9" />
                <path d="M12 15V5" />
                <path d="M18 15v-3" />
            </svg>
        </div>
    );
}

export function Brand({ suffix }: { suffix?: string }) {
    return (
        <div className="flex items-center gap-2.5">
            <BrandMark />
            <span className="text-[17px] font-extrabold">
                BuwisBuddy{suffix ? ` · ${suffix}` : ''}
            </span>
        </div>
    );
}
