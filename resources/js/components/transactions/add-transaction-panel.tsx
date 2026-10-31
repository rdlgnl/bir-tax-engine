import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ChoiceChips } from '@/components/choice-chips';
import { CertificateChecklist } from '@/components/transactions/certificate-checklist';
import { TransactionForm } from '@/components/transactions/transaction-form';
import { importMethod } from '@/routes/transactions';
import type { TransactionType, TransactionWithEffect } from '@/types';

type Mode = TransactionType | 'certificate';

type Props = {
    editing?: TransactionWithEffect | null;
    onDone?: () => void;
};

/** "What happened?": pick a kind of event, then fill in its form. */
export function AddTransactionPanel({ editing = null, onDone }: Props) {
    const context = usePage().props.transactionForm;
    const [mode, setMode] = useState<Mode>(editing?.type ?? 'payment');

    if (!context) {
        return null;
    }

    return (
        <div className="flex flex-col gap-4">
            <h2 className="text-lg font-extrabold">
                {editing ? 'Edit entry' : 'What happened?'}
            </h2>
            {!editing && (
                <ChoiceChips<Mode>
                    label="What happened?"
                    value={mode}
                    onChange={setMode}
                    options={[
                        { value: 'payment', label: 'Received money' },
                        { value: 'invoice', label: 'Issued invoice' },
                        { value: 'expense', label: 'Paid expense' },
                        { value: 'certificate', label: 'Got a 2307' },
                    ]}
                />
            )}
            {!editing && (
                <p className="-mt-1 text-[13px] text-muted-foreground">
                    Many at once?{' '}
                    <Link
                        href={importMethod().url}
                        className="font-bold text-primary hover:underline"
                    >
                        Import a statement (CSV)
                    </Link>
                </p>
            )}
            {mode === 'certificate' ? (
                <CertificateChecklist items={context.awaitingCertificates} />
            ) : (
                <TransactionForm
                    key={`${mode}-${editing?.id ?? 'new'}`}
                    type={mode}
                    context={context}
                    editing={editing}
                    onDone={onDone}
                />
            )}
        </div>
    );
}
