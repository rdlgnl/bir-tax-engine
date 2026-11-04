import { FieldError } from '@/components/field-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate, peso } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Answer, Row } from './types';

/** A line the app couldn't place: a client's payment, or personal money? */
export function AnswerCard({
    row,
    answer,
    error,
    onAnswer,
}: {
    row: Row;
    answer?: Answer;
    error?: string;
    onAnswer: (next: Partial<Answer>) => void;
}) {
    return (
        <section className="flex flex-col gap-3 rounded-card border border-warning-border bg-warning-soft p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-[15px]">
                    <strong>{row.date ? formatDate(row.date) : '—'}</strong> · “
                    {row.description}” · <strong>{peso(row.received)}</strong>
                </p>
                <div className="flex gap-2">
                    {(['payment', 'personal'] as const).map((kind) => (
                        <Button
                            key={kind}
                            type="button"
                            variant="outline"
                            aria-pressed={answer?.kind === kind}
                            onClick={() => onAnswer({ kind })}
                            className={cn(
                                'rounded-xl font-bold',
                                answer?.kind === kind &&
                                    'border-2 border-primary text-primary',
                            )}
                        >
                            {kind === 'payment'
                                ? 'Client payment'
                                : 'Personal, not income'}
                        </Button>
                    ))}
                </div>
            </div>
            {answer?.kind === 'payment' && (
                <div className="flex max-w-md flex-col gap-1.5">
                    <Label htmlFor={`client-${row.index}`}>Who paid you?</Label>
                    <Input
                        id={`client-${row.index}`}
                        value={answer.client}
                        onChange={(event) =>
                            onAnswer({ client: event.target.value })
                        }
                        className="h-11 rounded-xl bg-card"
                    />
                    <FieldError message={error} />
                </div>
            )}
        </section>
    );
}
