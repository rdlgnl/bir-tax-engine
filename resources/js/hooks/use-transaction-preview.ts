import { useServerPreview } from '@/hooks/use-server-preview';
import { preview } from '@/routes/transactions';
import type { TransactionForm, TransactionPreview } from '@/types';

/** What saving this entry would do to the tax, while it's being typed. */
export function useTransactionPreview(
    form: TransactionForm,
    editingId: number | null,
) {
    const { response, loading } = useServerPreview<
        {
            id: number | null;
            type: string;
            occurred_on: string;
            counterparty: string;
            amount: string;
            tax_withheld: string;
            has_valid_invoice: boolean;
        },
        TransactionPreview
    >(
        preview,
        {
            id: editingId,
            type: form.type,
            occurred_on: form.occurred_on,
            counterparty: form.counterparty,
            amount: form.amount,
            tax_withheld: form.tax_withheld,
            has_valid_invoice: form.has_valid_invoice,
        },
        Number(form.amount) > 0 && form.occurred_on !== '',
    );

    return { preview: response, loading };
}
