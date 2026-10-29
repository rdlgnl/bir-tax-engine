import { formatDate, minusPeso, peso, signedPeso } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { TransactionPreview, TransactionType } from '@/types';

type Props = {
    type: TransactionType;
    regime: string | null;
    preview: TransactionPreview | null;
    loading: boolean;
    amount: number;
    withheld: number;
    date: string;
};

/** "What this does to your tax", filled in by the tax engine as you type. */
export function PreviewBox({
    type,
    regime,
    preview,
    loading,
    amount,
    withheld,
    date,
}: Props) {
    if (!preview) {
        return null;
    }

    const method = regime === 'eight_percent' ? '8%' : 'graduated';

    return (
        <div
            aria-live="polite"
            aria-busy={loading}
            className={cn(
                'flex flex-col gap-1 rounded-xl border border-[#C9D7FB] bg-selected px-3.5 py-3 text-sm transition-opacity',
                loading && 'opacity-60',
            )}
        >
            <span className="text-xs font-extrabold tracking-[0.4px] text-accent-foreground">
                WHAT THIS DOES TO YOUR TAX
            </span>
            {type === 'expense' ? (
                <Row
                    label={
                        preview.tax_effect < 0
                            ? `Lowers Q${preview.quarter} tax`
                            : 'No effect on your tax'
                    }
                    value={
                        preview.tax_effect < 0
                            ? minusPeso(preview.tax_effect)
                            : '—'
                    }
                    tone={preview.tax_effect < 0 ? 'credit' : 'muted'}
                />
            ) : (
                <Row
                    label={
                        type === 'invoice'
                            ? `Counts in Q${preview.quarter} sales (${date ? formatDate(date) : ''})`
                            : `Adds to Q${preview.quarter} tax (${method})`
                    }
                    value={signedPeso(preview.tax_effect)}
                />
            )}
            {preview.credit > 0 && (
                <Row
                    label="Credit for tax withheld"
                    value={minusPeso(preview.credit)}
                    tone="credit"
                />
            )}
            {type === 'payment' && withheld > 0 && amount > withheld && (
                <span className="text-[13px] text-muted-foreground">
                    You received {peso(amount - withheld)} in your account.
                </span>
            )}
            {type === 'invoice' && (
                <span className="text-[13px] text-muted-foreground">
                    Mark it paid when the money arrives, and add any 2307 then.
                </span>
            )}
        </div>
    );
}

function Row({
    label,
    value,
    tone,
}: {
    label: string;
    value: string;
    tone?: 'credit' | 'muted';
}) {
    return (
        <div className="flex items-baseline justify-between gap-3">
            <span>{label}</span>
            <span
                className={cn(
                    'font-extrabold',
                    tone === 'credit' && 'text-success',
                    tone === 'muted' && 'font-semibold text-muted-foreground',
                )}
            >
                {value}
            </span>
        </div>
    );
}
