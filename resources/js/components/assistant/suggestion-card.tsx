import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { formatDate, peso } from '@/lib/format';
import { suggestion as suggestionRoute } from '@/routes/ask';
import { show as setupShow } from '@/routes/setup';
import { store as transactionsStore } from '@/routes/transactions';

type Status = 'open' | 'done' | 'dismissed';

export type Suggestion =
    | {
          kind: 'email';
          to: string | null;
          subject: string;
          body: string;
          status: Status;
      }
    | {
          kind: 'transaction';
          transaction: {
              type: 'payment' | 'invoice' | 'expense';
              occurred_on: string;
              counterparty: string;
              amount: number;
              tax_withheld?: number;
              category?: string;
              has_valid_invoice?: boolean;
              invoice_number?: string;
              description?: string;
          };
          status: Status;
      }
    | {
          kind: 'profile';
          step: string;
          step_label: string;
          change: string;
          why: string;
          status: Status;
      };

const typeLabels = {
    payment: 'money received',
    invoice: 'an invoice issued',
    expense: 'an expense',
};

/**
 * Something the assistant suggested, for the taxpayer to approve: nothing
 * changes until they do, and then through the app's own form and checks.
 */
export function SuggestionCard({
    suggestion,
    turn,
    index,
}: {
    suggestion: Suggestion;
    turn: number;
    index: number;
}) {
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [copied, setCopied] = useState(false);
    const mark = (status: 'done' | 'dismissed', then?: () => void) =>
        router.post(
            suggestionRoute().url,
            { turn, index, status },
            { preserveScroll: true, onSuccess: then },
        );

    if (suggestion.status !== 'open') {
        return (
            <p className="text-sm text-muted-foreground">
                {suggestion.status === 'dismissed'
                    ? 'Set aside.'
                    : suggestion.kind === 'transaction'
                      ? 'Added to your transactions.'
                      : 'Done.'}
            </p>
        );
    }

    const actions = (approve: React.ReactNode) => (
        <div className="mt-3 flex flex-wrap gap-2">
            {approve}
            <Button
                type="button"
                variant="ghost"
                className="rounded-xl"
                onClick={() => mark('dismissed')}
            >
                Not now
            </Button>
        </div>
    );

    return (
        <div className="rounded-xl border bg-card p-4 text-[15px]">
            {suggestion.kind === 'email' && (
                <>
                    <p className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                        Email draft
                    </p>
                    {suggestion.to && (
                        <p className="mt-2 text-sm">To: {suggestion.to}</p>
                    )}
                    <p className="mt-1 text-sm font-bold">
                        {suggestion.subject}
                    </p>
                    <p className="mt-2 rounded-lg bg-muted px-3 py-2 text-sm whitespace-pre-line">
                        {suggestion.body}
                    </p>
                    {actions(
                        <>
                            <Button
                                type="button"
                                className="rounded-xl font-bold"
                                onClick={() => {
                                    window.location.href = `mailto:${encodeURIComponent(suggestion.to ?? '')}?subject=${encodeURIComponent(suggestion.subject)}&body=${encodeURIComponent(suggestion.body)}`;
                                    mark('done');
                                }}
                            >
                                Open in email
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-xl font-bold"
                                onClick={() => {
                                    void navigator.clipboard
                                        .writeText(
                                            `${suggestion.subject}\n\n${suggestion.body}`,
                                        )
                                        .then(() => setCopied(true));
                                }}
                            >
                                {copied ? 'Copied' : 'Copy'}
                            </Button>
                        </>,
                    )}
                </>
            )}

            {suggestion.kind === 'transaction' && (
                <>
                    <p className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                        Add {typeLabels[suggestion.transaction.type]}?
                    </p>
                    <dl className="mt-2 divide-y divide-divider text-sm">
                        {[
                            [
                                'Date',
                                formatDate(suggestion.transaction.occurred_on),
                            ],
                            [
                                suggestion.transaction.type === 'expense'
                                    ? 'Paid to'
                                    : 'Client',
                                suggestion.transaction.counterparty,
                            ],
                            ['Amount', peso(suggestion.transaction.amount)],
                            ...(suggestion.transaction.tax_withheld
                                ? [
                                      [
                                          'Tax withheld',
                                          peso(
                                              suggestion.transaction
                                                  .tax_withheld,
                                          ),
                                      ],
                                  ]
                                : []),
                            ...(suggestion.transaction.invoice_number
                                ? [
                                      [
                                          'Invoice number',
                                          suggestion.transaction.invoice_number,
                                      ],
                                  ]
                                : []),
                            ...(suggestion.transaction.type === 'expense'
                                ? [
                                      [
                                          'Receipt in your name',
                                          suggestion.transaction
                                              .has_valid_invoice
                                              ? 'Yes'
                                              : 'No',
                                      ],
                                  ]
                                : []),
                            ...(suggestion.transaction.description
                                ? [['Note', suggestion.transaction.description]]
                                : []),
                        ].map(([label, value]) => (
                            <div
                                key={label}
                                className="flex justify-between gap-3 py-1.5"
                            >
                                <dt className="text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="text-right font-semibold">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                    {error && (
                        <p className="mt-2 text-sm text-destructive">{error}</p>
                    )}
                    {actions(
                        <Button
                            type="button"
                            disabled={busy}
                            className="rounded-xl font-bold"
                            onClick={() =>
                                router.post(
                                    transactionsStore().url,
                                    {
                                        tax_withheld: 0,
                                        ...suggestion.transaction,
                                    },
                                    {
                                        preserveScroll: true,
                                        onStart: () => {
                                            setBusy(true);
                                            setError(null);
                                        },
                                        onFinish: () => setBusy(false),
                                        onError: (errors) =>
                                            setError(
                                                Object.values(errors)[0] ??
                                                    "The app couldn't add it.",
                                            ),
                                        onSuccess: () => mark('done'),
                                    },
                                )
                            }
                        >
                            Add it
                        </Button>,
                    )}
                </>
            )}

            {suggestion.kind === 'profile' && (
                <>
                    <p className="text-[13px] font-extrabold tracking-[0.4px] text-slate-700 uppercase">
                        Change an answer · {suggestion.step_label}
                    </p>
                    <p className="mt-2 font-semibold">{suggestion.change}</p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {suggestion.why}
                    </p>
                    {actions(
                        <Button
                            type="button"
                            className="rounded-xl font-bold"
                            onClick={() =>
                                mark('done', () =>
                                    router.visit(
                                        setupShow(suggestion.step).url,
                                    ),
                                )
                            }
                        >
                            Open {suggestion.step_label}
                        </Button>,
                    )}
                </>
            )}
        </div>
    );
}
