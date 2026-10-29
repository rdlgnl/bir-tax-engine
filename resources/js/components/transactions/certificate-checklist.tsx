import { router } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import { certificate } from '@/routes/transactions';
import type { Transaction } from '@/types';

/** "Got a 2307": tick off the certificates clients have sent. */
export function CertificateChecklist({ items }: { items: Transaction[] }) {
    const [savingId, setSavingId] = useState<number | null>(null);

    if (items.length === 0) {
        return (
            <p className="flex items-center gap-2 rounded-xl bg-success-soft p-4 text-sm font-semibold text-success">
                <CheckCircle2 className="size-5" aria-hidden />
                Every 2307 you're owed is in. Nothing to track.
            </p>
        );
    }

    function markReceived(item: Transaction) {
        setSavingId(item.id);
        router.put(
            certificate(item.id).url,
            { received: true },
            {
                preserveScroll: true,
                onFinish: () => setSavingId(null),
            },
        );
    }

    return (
        <div className="flex flex-col gap-3">
            <p className="text-[13px] text-muted-foreground">
                A 2307 is your client's proof that they paid tax for you. Mark
                it when it arrives, so the credit is confirmed.
            </p>
            <ul className="flex flex-col divide-y divide-divider rounded-xl border">
                {items.map((item) => (
                    <li
                        key={item.id}
                        className="flex items-center justify-between gap-3 px-3.5 py-3"
                    >
                        <div className="min-w-0 text-sm">
                            <p className="truncate font-bold">
                                {item.counterparty}
                            </p>
                            <p className="text-[13px] text-muted-foreground">
                                {formatDate(item.occurred_on)} ·{' '}
                                {peso(item.tax_withheld)} withheld
                                {item.certificate_status === 'missing' && (
                                    <span className="font-semibold text-warning">
                                        {' '}
                                        · missing
                                    </span>
                                )}
                            </p>
                        </div>
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={savingId === item.id}
                            onClick={() => markReceived(item)}
                            aria-label={`Mark the 2307 from ${item.counterparty} for ${formatDate(item.occurred_on)} as received`}
                        >
                            {savingId === item.id ? 'Saving…' : 'Received'}
                        </Button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
