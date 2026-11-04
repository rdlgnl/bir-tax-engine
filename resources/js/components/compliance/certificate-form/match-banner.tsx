import { AlertTriangle, Check, Info } from 'lucide-react';
import { formatDate, peso, plural } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Match } from './types';

/** How the certificate compares with the payments recorded from that client. */
export function MatchBanner({
    result,
    client,
    quarter,
    claimed,
    loading,
}: {
    result: Match;
    client: string;
    quarter: number;
    claimed: number;
    loading: boolean;
}) {
    const one = result.payments.length === 1 ? result.payments[0] : null;
    const tone =
        result.status === 'exact'
            ? 'bg-success-soft text-success'
            : result.status === 'partial'
              ? 'bg-warning-soft text-warning'
              : 'bg-muted text-slate-700';
    const Icon =
        result.status === 'exact'
            ? Check
            : result.status === 'partial'
              ? AlertTriangle
              : Info;

    let message: string;

    if (result.status === 'exact') {
        message = one
            ? `Matches your ${formatDate(one.occurred_on)} payment from ${one.counterparty} (${peso(one.amount)}, ${peso(one.tax_withheld)} withheld). Saving confirms the credit.`
            : `Matches ${plural(result.payments.length, 'payment')} from ${client} in Q${quarter} (${peso(result.withheld)} withheld in total). Saving confirms the credit.`;
    } else if (result.status === 'partial') {
        message = `Your ${plural(result.payments.length, 'payment')} from ${client} in Q${quarter} show ${peso(result.withheld)} withheld, but this 2307 says ${peso(claimed)}. You can save it, but the credit stays unconfirmed until they match.`;
    } else {
        message = `No payment from ${client} in Q${quarter} has tax withheld yet. You can still save the certificate.`;
    }

    return (
        <p
            role="status"
            aria-busy={loading}
            className={cn(
                'flex items-start gap-2.5 rounded-2xl px-4 py-3.5 text-[15px] transition-opacity',
                tone,
                loading && 'opacity-60',
            )}
        >
            <Icon className="mt-0.5 size-5 shrink-0" aria-hidden />
            {message}
        </p>
    );
}
