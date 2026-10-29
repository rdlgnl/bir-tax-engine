import { useHttp } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import { preview as previewRoute } from '@/routes/transactions';
import type { TransactionForm, TransactionPreview } from '@/types';

type PreviewPayload = {
    id: number | null;
    type: TransactionForm['type'];
    occurred_on: string;
    counterparty: string;
    amount: string;
    tax_withheld: string;
    has_valid_invoice: boolean;
};

/**
 * Asks the tax engine what saving this entry would do, while it's being typed.
 * The server stays the only place tax is computed.
 */
export function useTransactionPreview(
    form: TransactionForm,
    editingId: number | null,
) {
    const payload = useMemo<PreviewPayload>(
        () => ({
            id: editingId,
            type: form.type,
            occurred_on: form.occurred_on,
            counterparty: form.counterparty,
            amount: form.amount,
            tax_withheld: form.tax_withheld,
            has_valid_invoice: form.has_valid_invoice,
        }),
        [
            editingId,
            form.type,
            form.occurred_on,
            form.counterparty,
            form.amount,
            form.tax_withheld,
            form.has_valid_invoice,
        ],
    );

    const ready = Number(payload.amount) > 0 && payload.occurred_on !== '';
    const http = useHttp<PreviewPayload, TransactionPreview>(payload);

    useEffect(() => {
        if (!ready) {
            return;
        }

        const timer = setTimeout(() => {
            http.cancel();
            http.transform(() => payload);
            http.submit(previewRoute()).catch(() => {
                // A half-typed form can fail validation; the preview just waits.
            });
        }, 300);

        return () => clearTimeout(timer);
        // http is a new object each render; the payload is what matters.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [payload, ready]);

    return {
        preview: ready ? http.response : null,
        loading: http.processing,
    };
}
