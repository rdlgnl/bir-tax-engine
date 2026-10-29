import {
    formatDate,
    minusPeso,
    peso,
    quarterMonths,
    signedPeso,
} from '@/lib/format';
import { cn } from '@/lib/utils';
import type { TransactionWithEffect } from '@/types';

type Props = {
    transactions: TransactionWithEffect[];
    regime: string | null;
    /** Group rows under "Q3 · JUL–SEP · ₱200,000" headings. */
    grouped?: boolean;
    /** Show the 2307 column instead of the tax effect. */
    showCertificates?: boolean;
    onSelect?: (transaction: TransactionWithEffect) => void;
    selectedId?: number | null;
};

export function describe(t: TransactionWithEffect): string {
    switch (t.type) {
        case 'invoice':
            return `Invoice ${t.invoice_number} · ${t.invoice_paid ? 'paid' : 'unpaid'}`;
        case 'expense':
            return `Expense · ${t.category_label ?? 'other'}`;
        default:
            return 'Payment received';
    }
}

function effectLabel(t: TransactionWithEffect, regime: string | null): string {
    if (t.tax_effect === 0) {
        return t.type === 'expense' && regime === 'eight_percent'
            ? 'None on 8%'
            : '—';
    }

    return signedPeso(t.tax_effect);
}

function CertificateCell({ t }: { t: TransactionWithEffect }) {
    switch (t.certificate_status) {
        case 'received':
            return <span className="font-semibold text-success">Received</span>;
        case 'missing':
            return (
                <span className="font-semibold text-warning underline">
                    Missing
                </span>
            );
        case 'pending':
            return (
                <span className="text-muted-foreground">
                    Due after Q{t.quarter}
                </span>
            );
        default:
            return <span className="text-muted-foreground">—</span>;
    }
}

export function TransactionTable({
    transactions,
    regime,
    grouped = false,
    showCertificates = false,
    onSelect,
    selectedId,
}: Props) {
    const groups = grouped
        ? Object.entries(
              Object.groupBy(transactions, (t) => String(t.quarter)),
          ).sort(([a], [b]) => Number(b) - Number(a))
        : [['all', transactions] as const];

    return (
        <div className="-mx-2 overflow-x-auto px-2">
            <table className="w-full min-w-[640px] text-sm">
                <caption className="sr-only">Transactions</caption>
                <thead>
                    <tr className="text-left text-[13px] font-bold text-slate-700">
                        <th scope="col" className="py-2 pr-3 font-bold">
                            Date
                        </th>
                        <th scope="col" className="py-2 pr-3 font-bold">
                            Client / payee
                        </th>
                        <th scope="col" className="py-2 pr-3 font-bold">
                            What
                        </th>
                        <th
                            scope="col"
                            className="py-2 pr-3 text-right font-bold"
                        >
                            Amount
                        </th>
                        <th
                            scope="col"
                            className="py-2 pr-3 text-right font-bold"
                        >
                            Withheld
                        </th>
                        <th
                            scope="col"
                            className={cn(
                                'py-2 font-bold',
                                showCertificates ? 'text-left' : 'text-right',
                            )}
                        >
                            {showCertificates ? '2307' : 'Tax effect'}
                        </th>
                    </tr>
                </thead>
                {groups.map(([key, rows]) => {
                    const items = rows ?? [];
                    const sales = items
                        .filter((t) => t.type !== 'expense')
                        .reduce((sum, t) => sum + t.amount, 0);

                    return (
                        <tbody key={key}>
                            {grouped && (
                                <tr>
                                    <th
                                        colSpan={6}
                                        scope="rowgroup"
                                        className="border-b border-divider pt-4 pb-2 text-left text-xs font-extrabold tracking-[0.4px] text-slate-700 uppercase"
                                    >
                                        Q{key} · {quarterMonths(Number(key))} ·{' '}
                                        {peso(sales)}
                                    </th>
                                </tr>
                            )}
                            {items.map((t) => (
                                <tr
                                    key={t.id}
                                    onClick={
                                        onSelect ? () => onSelect(t) : undefined
                                    }
                                    className={cn(
                                        'border-b border-divider last:border-0',
                                        onSelect &&
                                            'cursor-pointer hover:bg-muted/60',
                                        t.certificate_status === 'missing' &&
                                            'bg-warning-soft',
                                        selectedId === t.id && 'bg-accent/60',
                                    )}
                                >
                                    <td className="py-3 pr-3 whitespace-nowrap">
                                        {formatDate(t.occurred_on)}
                                    </td>
                                    <td className="py-3 pr-3 font-bold">
                                        {onSelect ? (
                                            <button
                                                type="button"
                                                className="text-left hover:underline"
                                                aria-label={`Edit ${t.counterparty}, ${formatDate(t.occurred_on)}`}
                                            >
                                                {t.counterparty}
                                            </button>
                                        ) : (
                                            t.counterparty
                                        )}
                                    </td>
                                    <td className="py-3 pr-3">{describe(t)}</td>
                                    <td
                                        className={cn(
                                            'py-3 pr-3 text-right font-semibold whitespace-nowrap',
                                            t.type === 'expense'
                                                ? 'font-normal text-muted-foreground'
                                                : 'font-extrabold',
                                        )}
                                    >
                                        {t.type === 'expense'
                                            ? minusPeso(t.amount)
                                            : peso(t.amount)}
                                    </td>
                                    <td className="py-3 pr-3 text-right whitespace-nowrap">
                                        {t.tax_withheld > 0
                                            ? peso(t.tax_withheld)
                                            : '—'}
                                    </td>
                                    <td
                                        className={cn(
                                            'py-3 whitespace-nowrap',
                                            showCertificates
                                                ? 'text-left'
                                                : 'text-right',
                                            !showCertificates &&
                                                t.tax_effect === 0 &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        {showCertificates ? (
                                            <CertificateCell t={t} />
                                        ) : (
                                            effectLabel(t, regime)
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    );
                })}
            </table>
        </div>
    );
}
